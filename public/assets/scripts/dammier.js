/**
 * dammier.js
 *
 * Moteur du casse-tete hebdomadaire:
 * - interaction directe sur le plateau
 * - clic piece puis case d'arrivee
 * - indice optionnel via bouton ampoule
 * - enregistrement du score pour les membres connectes
 * - verrouillage apres resolution pour eviter de rejouer le meme puzzle
 */

function initDammierBoardGame() {
    const dammierRoot = document.querySelector("[data-dammier-root]");

    if (!(dammierRoot instanceof HTMLElement)) {
        return;
    }

    const payloadNode = dammierRoot.querySelector("[data-dammier-payload]");
    const boardNode = dammierRoot.querySelector("[data-dammier-board]");
    const promptNode = dammierRoot.querySelector("[data-dammier-prompt]");
    const selectionNode = dammierRoot.querySelector("[data-dammier-selection]");
    const feedbackNode = dammierRoot.querySelector("[data-dammier-feedback]");
    const hintTextNode = dammierRoot.querySelector("[data-dammier-hint-text]");
    const timerNode = dammierRoot.querySelector("[data-dammier-timer]");
    const resetButton = dammierRoot.querySelector("[data-dammier-reset]");
    const hintButton = dammierRoot.querySelector("[data-dammier-hint-toggle]");
    const rankingListNode = dammierRoot.querySelector("[data-dammier-ranking-list]");
    const rankingEmptyNode = dammierRoot.querySelector("[data-dammier-ranking-empty]");
    const submitUrl = dammierRoot.getAttribute("data-dammier-submit-url") || window.location.pathname;
    const csrfToken = dammierRoot.getAttribute("data-dammier-csrf") || "";
    const isAuthenticated = dammierRoot.getAttribute("data-dammier-is-authenticated") === "true";

    if (!(payloadNode instanceof HTMLScriptElement) || !(boardNode instanceof HTMLElement)) {
        return;
    }

    let payload = null;

    try {
        payload = JSON.parse(payloadNode.textContent || "{}");
    } catch (error) {
        return;
    }

    const puzzle = payload?.dammier_puzzle;

    if (!puzzle || !Array.isArray(puzzle.dammier_solution)) {
        return;
    }

    const puzzleStorageKey = `dammier-played-${String(puzzle.dammier_week_key || "")}-${String(puzzle.dammier_id || "")}`;
    let scoreReference = payload?.dammier_previous_score && typeof payload.dammier_previous_score === "object"
        ? payload.dammier_previous_score
        : null;

    const unicodePieces = {
        p: "\u265F",
        r: "\u265C",
        n: "\u265E",
        b: "\u265D",
        q: "\u265B",
        k: "\u265A",
        P: "\u2659",
        R: "\u2656",
        N: "\u2658",
        B: "\u2657",
        Q: "\u2655",
        K: "\u2654",
    };
    const pieceNames = {
        p: "pion noir",
        r: "tour noire",
        n: "cavalier noir",
        b: "fou noir",
        q: "dame noire",
        k: "roi noir",
        P: "pion blanc",
        R: "tour blanche",
        N: "cavalier blanc",
        B: "fou blanc",
        Q: "dame blanche",
        K: "roi blanc",
    };
    const files = ["a", "b", "c", "d", "e", "f", "g", "h"];
    const sideToMove = String(puzzle.dammier_side_to_move || "w");
    const opponentSide = sideToMove === "w" ? "b" : "w";
    const replySequence = Array.isArray(puzzle.dammier_replies) ? puzzle.dammier_replies : [];
    let stepIndex = 0;
    let movesCount = 0;
    let startedAt = null;
    let timerId = null;
    let isSolved = false;
    let isLocked = payload?.dammier_already_played === true;
    let selectedSquare = "";
    let boardState = {};
    let lastWrongTarget = "";
    let focusedSquare = String(puzzle.dammier_solution?.[0] || "").slice(0, 2) || "a1";

    function formatElapsed(seconds) {
        const safeSeconds = Math.max(0, seconds);
        const minutes = String(Math.floor(safeSeconds / 60)).padStart(2, "0");
        const remainder = String(safeSeconds % 60).padStart(2, "0");

        return `${minutes}:${remainder}`;
    }

    function getElapsedSeconds() {
        if (startedAt === null) {
            return 0;
        }

        return Math.floor((Date.now() - startedAt) / 1000);
    }

    function renderTimer() {
        if (timerNode instanceof HTMLElement) {
            timerNode.textContent = formatElapsed(getElapsedSeconds());
        }
    }

    function hasGuestPlayedCurrentPuzzle() {
        try {
            return window.localStorage.getItem(puzzleStorageKey) === "played";
        } catch (error) {
            return false;
        }
    }

    function rememberGuestPlayedCurrentPuzzle() {
        try {
            window.localStorage.setItem(puzzleStorageKey, "played");
        } catch (error) {
            // Ignore localStorage errors and keep the puzzle available.
        }
    }

    if (!isAuthenticated && hasGuestPlayedCurrentPuzzle()) {
        isLocked = true;
    }

    function formatMoveForDisplay(move) {
        const from = String(move).slice(0, 2);
        const to = String(move).slice(2, 4);

        if (from.length < 2 || to.length < 2) {
            return "";
        }

        return `${from} -> ${to}`;
    }

    function parseFenBoard(fen) {
        const [boardPart] = String(fen || "").split(" ");
        const ranks = boardPart.split("/");
        const squares = [];
        let squareIndex = 0;

        ranks.forEach((rank, rankIndex) => {
            Array.from(rank).forEach((character) => {
                const emptyCount = Number(character);

                if (!Number.isNaN(emptyCount) && emptyCount > 0) {
                    for (let index = 0; index < emptyCount; index += 1) {
                        const coordinate = `${files[squareIndex % 8]}${8 - rankIndex}`;
                        squares.push({
                            pieceCode: "",
                            piece: "",
                            coordinate,
                        });
                        squareIndex += 1;
                    }

                    return;
                }

                const coordinate = `${files[squareIndex % 8]}${8 - rankIndex}`;
                squares.push({
                    pieceCode: character,
                    piece: unicodePieces[character] || "",
                    coordinate,
                });
                squareIndex += 1;
            });
        });

        return squares;
    }

    function buildBoardState() {
        const nextState = {};

        parseFenBoard(puzzle.dammier_fen).forEach((square) => {
            nextState[square.coordinate] = square.pieceCode;
        });

        return nextState;
    }

    function getPieceSide(pieceCode) {
        if (pieceCode === "") {
            return "";
        }

        return pieceCode === pieceCode.toUpperCase() ? "w" : "b";
    }

    function coordinateToIndex(coordinate) {
        return {
            file: files.indexOf(String(coordinate).charAt(0)),
            rank: Number(String(coordinate).charAt(1)) - 1,
        };
    }

    function indexToCoordinate(fileIndex, rankIndex) {
        return `${files[fileIndex]}${rankIndex + 1}`;
    }

    function isInsideBoard(fileIndex, rankIndex) {
        return fileIndex >= 0 && fileIndex < 8 && rankIndex >= 0 && rankIndex < 8;
    }

    function cloneBoardState(sourceBoard) {
        return { ...sourceBoard };
    }

    function isPathClear(from, to, currentBoard) {
        const fromIndex = coordinateToIndex(from);
        const toIndex = coordinateToIndex(to);
        const fileStep = Math.sign(toIndex.file - fromIndex.file);
        const rankStep = Math.sign(toIndex.rank - fromIndex.rank);
        let fileIndex = fromIndex.file + fileStep;
        let rankIndex = fromIndex.rank + rankStep;

        while (fileIndex !== toIndex.file || rankIndex !== toIndex.rank) {
            const intermediateCoordinate = indexToCoordinate(fileIndex, rankIndex);

            if (String(currentBoard[intermediateCoordinate] || "") !== "") {
                return false;
            }

            fileIndex += fileStep;
            rankIndex += rankStep;
        }

        return true;
    }

    function isPseudoLegalMove(from, to, currentBoard, movingSide) {
        const movingPiece = String(currentBoard[from] || "");
        const targetPiece = String(currentBoard[to] || "");

        if (movingPiece === "" || from === to || getPieceSide(movingPiece) !== movingSide) {
            return false;
        }

        if (targetPiece !== "" && getPieceSide(targetPiece) === movingSide) {
            return false;
        }

        const fromIndex = coordinateToIndex(from);
        const toIndex = coordinateToIndex(to);
        const fileDiff = toIndex.file - fromIndex.file;
        const rankDiff = toIndex.rank - fromIndex.rank;
        const absoluteFileDiff = Math.abs(fileDiff);
        const absoluteRankDiff = Math.abs(rankDiff);
        const pieceType = movingPiece.toLowerCase();

        if (pieceType === "p") {
            const forward = movingSide === "w" ? 1 : -1;
            const startRank = movingSide === "w" ? 1 : 6;

            if (fileDiff === 0 && targetPiece === "") {
                if (rankDiff === forward) {
                    return true;
                }

                if (
                    rankDiff === forward * 2
                    && fromIndex.rank === startRank
                    && String(currentBoard[indexToCoordinate(fromIndex.file, fromIndex.rank + forward)] || "") === ""
                ) {
                    return true;
                }
            }

            if (absoluteFileDiff === 1 && rankDiff === forward && targetPiece !== "" && getPieceSide(targetPiece) !== movingSide) {
                return true;
            }

            return false;
        }

        if (pieceType === "n") {
            return (absoluteFileDiff === 1 && absoluteRankDiff === 2) || (absoluteFileDiff === 2 && absoluteRankDiff === 1);
        }

        if (pieceType === "b") {
            return absoluteFileDiff === absoluteRankDiff && isPathClear(from, to, currentBoard);
        }

        if (pieceType === "r") {
            return (fileDiff === 0 || rankDiff === 0) && isPathClear(from, to, currentBoard);
        }

        if (pieceType === "q") {
            return (
                (absoluteFileDiff === absoluteRankDiff || fileDiff === 0 || rankDiff === 0)
                && isPathClear(from, to, currentBoard)
            );
        }

        if (pieceType === "k") {
            return absoluteFileDiff <= 1 && absoluteRankDiff <= 1;
        }

        return false;
    }

    function findKingCoordinate(currentBoard, side) {
        const searchedPiece = side === "w" ? "K" : "k";

        return Object.keys(currentBoard).find((coordinate) => String(currentBoard[coordinate] || "") === searchedPiece) || "";
    }

    function isSquareAttacked(square, attackerSide, currentBoard) {
        return Object.keys(currentBoard).some((coordinate) => {
            const pieceCode = String(currentBoard[coordinate] || "");

            if (pieceCode === "" || getPieceSide(pieceCode) !== attackerSide) {
                return false;
            }

            const fromIndex = coordinateToIndex(coordinate);
            const toIndex = coordinateToIndex(square);
            const fileDiff = toIndex.file - fromIndex.file;
            const rankDiff = toIndex.rank - fromIndex.rank;
            const absoluteFileDiff = Math.abs(fileDiff);
            const absoluteRankDiff = Math.abs(rankDiff);
            const pieceType = pieceCode.toLowerCase();

            if (pieceType === "p") {
                const forward = attackerSide === "w" ? 1 : -1;

                return absoluteFileDiff === 1 && rankDiff === forward;
            }

            if (pieceType === "n") {
                return (absoluteFileDiff === 1 && absoluteRankDiff === 2) || (absoluteFileDiff === 2 && absoluteRankDiff === 1);
            }

            if (pieceType === "b") {
                return absoluteFileDiff === absoluteRankDiff && isPathClear(coordinate, square, currentBoard);
            }

            if (pieceType === "r") {
                return (fileDiff === 0 || rankDiff === 0) && isPathClear(coordinate, square, currentBoard);
            }

            if (pieceType === "q") {
                return (
                    (absoluteFileDiff === absoluteRankDiff || fileDiff === 0 || rankDiff === 0)
                    && isPathClear(coordinate, square, currentBoard)
                );
            }

            if (pieceType === "k") {
                return absoluteFileDiff <= 1 && absoluteRankDiff <= 1;
            }

            return false;
        });
    }

    function isLegalMove(from, to, currentBoard, movingSide) {
        if (!isPseudoLegalMove(from, to, currentBoard, movingSide)) {
            return false;
        }

        const simulatedBoard = cloneBoardState(currentBoard);
        simulatedBoard[to] = simulatedBoard[from];
        simulatedBoard[from] = "";

        const kingSquare = findKingCoordinate(simulatedBoard, movingSide);

        if (kingSquare === "") {
            return false;
        }

        return !isSquareAttacked(kingSquare, movingSide === "w" ? "b" : "w", simulatedBoard);
    }

    function isOwnPiece(pieceCode) {
        if (pieceCode === "") {
            return false;
        }

        return sideToMove === "w" ? pieceCode === pieceCode.toUpperCase() : pieceCode === pieceCode.toLowerCase();
    }

    function applyMoveToBoardState(move) {
        const from = String(move).slice(0, 2);
        const to = String(move).slice(2, 4);
        const movingPiece = boardState[from] || "";

        boardState[to] = movingPiece;
        boardState[from] = "";
    }

    function applyAutomaticReply(move) {
        const from = String(move).slice(0, 2);
        const to = String(move).slice(2, 4);

        if (from.length < 2 || to.length < 2 || !isLegalMove(from, to, boardState, opponentSide)) {
            return false;
        }

        applyMoveToBoardState(move);

        return true;
    }

    function syncSelectionText() {
        if (!(selectionNode instanceof HTMLElement)) {
            return;
        }

        if (selectedSquare === "") {
            selectionNode.textContent = "Aucune piece selectionnee.";
            return;
        }

        selectionNode.textContent = `Piece selectionnee : ${selectedSquare}. Choisis la case d'arrivee.`;
    }

    function setSelectionText(message) {
        if (selectionNode instanceof HTMLElement) {
            selectionNode.textContent = message;
        }
    }

    function buildSquareLabel(square) {
        const pieceCode = String(boardState[square.coordinate] || "");
        const pieceLabel = pieceCode !== "" ? pieceNames[pieceCode] || "piece" : "case vide";
        const selectionLabel = square.coordinate === selectedSquare ? ", piece selectionnee" : "";
        const warningLabel = square.coordinate === lastWrongTarget ? ", derniere tentative incorrecte" : "";

        return `Case ${square.coordinate}, ${pieceLabel}${selectionLabel}${warningLabel}.`;
    }

    function focusSquare(coordinate) {
        focusedSquare = coordinate;
        const squareNode = boardNode.querySelector(`[data-dammier-coordinate="${coordinate}"]`);

        if (squareNode instanceof HTMLButtonElement) {
            squareNode.focus();
        }
    }

    function moveFocusFromSquare(originCoordinate, fileDelta, rankDelta) {
        const origin = coordinateToIndex(originCoordinate);
        const nextFile = origin.file + fileDelta;
        const nextRank = origin.rank + rankDelta;

        if (!isInsideBoard(nextFile, nextRank)) {
            return;
        }

        focusSquare(indexToCoordinate(nextFile, nextRank));
    }

    function renderBoard() {
        const squares = parseFenBoard(puzzle.dammier_fen).map((square) => ({
            ...square,
            pieceCode: boardState[square.coordinate] || "",
            piece: unicodePieces[boardState[square.coordinate] || ""] || "",
        }));

        boardNode.innerHTML = "";

        squares.forEach((square, index) => {
            const squareNode = document.createElement("button");
            const row = Math.floor(index / 8);
            const column = index % 8;
            const isLight = (row + column) % 2 === 0;

            squareNode.type = "button";
            squareNode.className = "dammier_square";
            squareNode.setAttribute("data-dammier-color", isLight ? "light" : "dark");
            squareNode.setAttribute("aria-label", buildSquareLabel(square));
            squareNode.setAttribute("data-dammier-coordinate", square.coordinate);
            squareNode.setAttribute("data-dammier-file", row === 7 ? square.coordinate.charAt(0) : "");
            squareNode.setAttribute("data-dammier-rank", column === 0 ? square.coordinate.charAt(1) : "");
            squareNode.setAttribute("tabindex", square.coordinate === focusedSquare ? "0" : "-1");
            squareNode.classList.toggle("is-selected", square.coordinate === selectedSquare);
            squareNode.classList.toggle("is-wrong", square.coordinate === lastWrongTarget);
            squareNode.disabled = isLocked;

            if (square.piece !== "") {
                const pieceNode = document.createElement("span");
                pieceNode.className = "dammier_piece";
                pieceNode.setAttribute("data-dammier-piece-color", square.pieceCode === square.pieceCode.toUpperCase() ? "white" : "black");
                pieceNode.textContent = square.piece;
                squareNode.appendChild(pieceNode);
            }

            squareNode.addEventListener("click", () => {
                focusedSquare = square.coordinate;
                handleSquareClick(square.coordinate);
            });

            squareNode.addEventListener("focus", () => {
                focusedSquare = square.coordinate;
            });

            squareNode.addEventListener("keydown", (event) => {
                if (event.key === "ArrowLeft") {
                    event.preventDefault();
                    moveFocusFromSquare(square.coordinate, -1, 0);
                    return;
                }

                if (event.key === "ArrowRight") {
                    event.preventDefault();
                    moveFocusFromSquare(square.coordinate, 1, 0);
                    return;
                }

                if (event.key === "ArrowUp") {
                    event.preventDefault();
                    moveFocusFromSquare(square.coordinate, 0, 1);
                    return;
                }

                if (event.key === "ArrowDown") {
                    event.preventDefault();
                    moveFocusFromSquare(square.coordinate, 0, -1);
                    return;
                }

                if (event.key === "Home") {
                    event.preventDefault();
                    focusSquare(`${files[0]}${square.coordinate.charAt(1)}`);
                    return;
                }

                if (event.key === "End") {
                    event.preventDefault();
                    focusSquare(`${files[files.length - 1]}${square.coordinate.charAt(1)}`);
                }
            });

            boardNode.appendChild(squareNode);
        });
    }

    function setFeedback(message, state) {
        if (!(feedbackNode instanceof HTMLElement)) {
            return;
        }

        feedbackNode.textContent = message;
        feedbackNode.classList.remove("is-success", "is-error");

        if (state === "success" || state === "error") {
            feedbackNode.classList.add(`is-${state}`);
        }
    }

    function clearHint() {
        if (!(hintTextNode instanceof HTMLElement)) {
            return;
        }

        hintTextNode.hidden = true;
        hintTextNode.textContent = "";

        if (hintButton instanceof HTMLButtonElement) {
            hintButton.setAttribute("aria-expanded", "false");
            hintButton.setAttribute("aria-label", "Afficher un indice");
        }
    }

    function setControlsDisabled(disabled) {
        if (resetButton instanceof HTMLButtonElement) {
            resetButton.disabled = disabled;
        }

        if (hintButton instanceof HTMLButtonElement) {
            hintButton.disabled = disabled;
        }
    }

    function buildLockedMessage() {
        const movesCountValue = Number(scoreReference?.dammier_moves_count || 0);
        const elapsedSecondsValue = Number(scoreReference?.dammier_elapsed_seconds || 0);

        if (movesCountValue > 0 && elapsedSecondsValue > 0) {
            return `Tu as deja joue ce casse-tete cette semaine. Score conserve : ${movesCountValue} coups en ${formatElapsed(elapsedSecondsValue)}.`;
        }

        if (isAuthenticated) {
            return "Tu as deja joue ce casse-tete cette semaine. Une seule participation est conservee.";
        }

        return "Ce casse-tete a deja ete resolu sur ce navigateur. Reviens la semaine prochaine pour le nouveau puzzle.";
    }

    function applyPuzzleLock(message) {
        isLocked = true;
        isSolved = true;
        selectedSquare = "";
        lastWrongTarget = "";
        stopTimer();
        clearHint();
        setControlsDisabled(true);
        renderBoard();
        syncSelectionText();
        setSelectionText("Participation deja enregistree pour ce puzzle.");

        if (promptNode instanceof HTMLElement) {
            promptNode.textContent = "Casse-tete deja termine pour cette semaine.";
        }

        setFeedback(message || buildLockedMessage(), "success");
    }

    function toggleHint() {
        if (!(hintTextNode instanceof HTMLElement)) {
            return;
        }

        const hints = Array.isArray(puzzle.dammier_hints) ? puzzle.dammier_hints : [];
        hintTextNode.textContent = String(hints[stepIndex] || "Observe les lignes ouvertes, les echecs et les mats possibles.");
        hintTextNode.hidden = !hintTextNode.hidden;

        if (hintButton instanceof HTMLButtonElement) {
            const isExpanded = !hintTextNode.hidden;
            hintButton.setAttribute("aria-expanded", isExpanded ? "true" : "false");
            hintButton.setAttribute("aria-label", isExpanded ? "Masquer l'indice" : "Afficher un indice");
        }
    }

    function renderRanking(scores) {
        if (!(rankingListNode instanceof HTMLElement)) {
            return;
        }

        rankingListNode.innerHTML = "";

        scores.forEach((score) => {
            const item = document.createElement("li");
            const name = document.createElement("span");
            const moves = document.createElement("span");
            const elapsed = document.createElement("span");

            item.className = "dammier_ranking_item";
            name.textContent = String(score.dammier_display_name || "Membre");
            moves.textContent = `${Number(score.dammier_moves_count || 0)} coups`;
            elapsed.textContent = formatElapsed(Number(score.dammier_elapsed_seconds || 0));

            item.append(name, moves, elapsed);
            rankingListNode.appendChild(item);
        });

        if (rankingEmptyNode instanceof HTMLElement) {
            rankingEmptyNode.hidden = scores.length > 0;
        }
    }

    function stopTimer() {
        if (timerId !== null) {
            window.clearInterval(timerId);
            timerId = null;
        }
    }

    function startTimer() {
        if (startedAt !== null || isLocked) {
            return;
        }

        startedAt = Date.now();
        stopTimer();

        const tick = () => {
            renderTimer();
        };

        tick();
        timerId = window.setInterval(tick, 1000);
    }

    function renderStep() {
        if (!(promptNode instanceof HTMLElement)) {
            return;
        }

        if (isLocked) {
            promptNode.textContent = "Casse-tete deja termine pour cette semaine.";
            return;
        }

        if (stepIndex >= puzzle.dammier_solution.length) {
            promptNode.textContent = "Puzzle termine.";
            return;
        }

        promptNode.textContent = puzzle.dammier_solution.length === 1
            ? "Trouve le coup gagnant. Clique sur une piece, puis sur sa case d'arrivee."
            : `Trouve le coup ${stepIndex + 1} sur ${puzzle.dammier_solution.length}. Clique sur une piece, puis sur sa case d'arrivee.`;
    }

    function submitScore() {
        if (!isAuthenticated) {
            rememberGuestPlayedCurrentPuzzle();
            applyPuzzleLock("Bravo. Ce casse-tete est maintenant verrouille sur ce navigateur jusqu'au prochain puzzle hebdomadaire.");
            return;
        }

        const formData = new FormData();
        formData.append("action", "soumettre_resultat_dammier");
        formData.append("_token", csrfToken);
        formData.append("jeton_csrf", csrfToken);
        formData.append("dammier_puzzle_id", String(puzzle.dammier_id || ""));
        formData.append("dammier_week_key", String(puzzle.dammier_week_key || ""));
        formData.append("dammier_moves_count", String(movesCount));
        formData.append("dammier_elapsed_seconds", String(Math.max(1, getElapsedSeconds())));

        window.fetch(submitUrl, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: formData,
        })
            .then(async (response) => {
                let result = null;

                try {
                    result = await response.json();
                } catch (error) {
                    result = null;
                }

                if (response.status === 419) {
                    return {
                        success: false,
                        message: "Ta session a expire. Recharge la page puis reconnecte-toi si besoin.",
                    };
                }

                if (result && typeof result === "object") {
                    return result;
                }

                return {
                    success: false,
                    message: response.ok
                        ? "Le score n'a pas pu etre confirme."
                        : "Le score n'a pas pu etre envoye.",
                };
            })
            .then((result) => {
                if (!result?.success) {
                    setFeedback(result?.message || "Score non enregistre.", "error");
                    return;
                }

                if (result?.dammier_score && typeof result.dammier_score === "object") {
                    scoreReference = result.dammier_score;
                }

                renderRanking(Array.isArray(result.dammier_classement) ? result.dammier_classement : []);
                applyPuzzleLock(result?.message || "Puzzle resolu. Ton score est enregistre dans le classement.");
            })
            .catch(() => {
                setFeedback("Le puzzle est resolu, mais l'enregistrement du score a echoue.", "error");
            });
    }

    function handleMove(move) {
        if (isSolved || isLocked) {
            return;
        }

        movesCount += 1;
        const expectedMove = String(puzzle.dammier_solution[stepIndex] || "");

        if (String(move) === expectedMove) {
            applyMoveToBoardState(move);
            const automaticReply = String(replySequence[stepIndex] || "");
            selectedSquare = "";
            lastWrongTarget = "";

            if (automaticReply !== "" && !applyAutomaticReply(automaticReply)) {
                renderBoard();
                syncSelectionText();
                stopTimer();
                focusSquare(String(move).slice(2, 4));
                setFeedback("La reponse automatique du puzzle est invalide.", "error");
                return;
            }

            stepIndex += 1;
            renderBoard();
            syncSelectionText();
            focusSquare(String(move).slice(2, 4));

            if (stepIndex >= puzzle.dammier_solution.length) {
                isSolved = true;
                isLocked = true;
                stopTimer();
                clearHint();
                setControlsDisabled(true);
                if (promptNode instanceof HTMLElement) {
                    promptNode.textContent = "Bravo, le casse-tete est termine.";
                }
                setFeedback("Puzzle resolu. Verification du score en cours...", "success");
                submitScore();
                return;
            }

            clearHint();
            setFeedback(
                automaticReply !== ""
                    ? `Bien joue. Reponse noire : ${formatMoveForDisplay(automaticReply)}. Trouve maintenant le coup suivant.`
                    : "Bien joue. Cherche maintenant le coup suivant.",
                "success"
            );
            renderStep();
            return;
        }

        lastWrongTarget = String(move).slice(2, 4);
        selectedSquare = "";
        renderBoard();
        syncSelectionText();
        focusSquare(lastWrongTarget);
        setFeedback("Ce n'est pas le bon coup. Le score ajoute une tentative.", "error");
    }

    function handleSquareClick(coordinate) {
        if (isSolved || isLocked) {
            if (isLocked) {
                setFeedback(buildLockedMessage(), "success");
            }

            return;
        }

        const pieceCode = String(boardState[coordinate] || "");

        if (selectedSquare === "") {
            if (!isOwnPiece(pieceCode)) {
                setFeedback("Selectionne d'abord une piece de ton camp.", "error");
                return;
            }

            startTimer();
            selectedSquare = coordinate;
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            focusSquare(coordinate);
            setFeedback("Piece selectionnee. Choisis maintenant sa destination.", "");
            return;
        }

        if (coordinate === selectedSquare) {
            selectedSquare = "";
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            focusSquare(coordinate);
            setFeedback("Selection annulee.", "");
            return;
        }

        if (isOwnPiece(pieceCode)) {
            selectedSquare = coordinate;
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            focusSquare(coordinate);
            setFeedback("Piece changee. Choisis sa destination.", "");
            return;
        }

        if (!isLegalMove(selectedSquare, coordinate, boardState, sideToMove)) {
            lastWrongTarget = coordinate;
            selectedSquare = "";
            renderBoard();
            syncSelectionText();
            focusSquare(coordinate);
            setFeedback("Ce coup est illegal selon les regles des echecs.", "error");
            return;
        }

        handleMove(`${selectedSquare}${coordinate}`);
    }

    function resetPuzzle() {
        if (isLocked) {
            applyPuzzleLock(buildLockedMessage());
            return;
        }

        stepIndex = 0;
        movesCount = 0;
        startedAt = null;
        isSolved = false;
        selectedSquare = "";
        lastWrongTarget = "";
        focusedSquare = String(puzzle.dammier_solution?.[0] || "").slice(0, 2) || "a1";
        stopTimer();
        boardState = buildBoardState();
        renderBoard();
        renderStep();
        syncSelectionText();
        renderTimer();
        clearHint();
        setControlsDisabled(false);
        setFeedback("Le score compte le nombre total de tentatives jusqu'a la resolution.", "");
        focusSquare(focusedSquare);
    }

    boardState = buildBoardState();
    renderBoard();
    renderRanking(Array.isArray(payload?.dammier_classement) ? payload.dammier_classement : []);
    renderStep();
    syncSelectionText();
    renderTimer();
    setControlsDisabled(false);

    if (isLocked) {
        applyPuzzleLock(buildLockedMessage());
    }

    if (resetButton instanceof HTMLButtonElement) {
        resetButton.addEventListener("click", resetPuzzle);
    }

    if (hintButton instanceof HTMLButtonElement) {
        hintButton.addEventListener("click", toggleHint);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    initDammierBoardGame();
});
