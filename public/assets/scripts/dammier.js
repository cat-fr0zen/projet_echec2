/**
 * dammier.js
 *
 * Moteur du casse-tête hebdomadaire:
 * - interaction directe sur le plateau
 * - clic piece puis case d'arrivee
 * - indice optionnel via bouton ampoule
 * - enregistrement du score pour les membres connectes
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
            selectionNode.textContent = "Aucune pièce sélectionnée.";
            return;
        }

        selectionNode.textContent = `Pièce sélectionnée : ${selectedSquare}. Choisis la case d'arrivée.`;
    }

    function buildSquareLabel(square) {
        const pieceCode = String(boardState[square.coordinate] || "");
        const pieceLabel = pieceCode !== "" ? pieceNames[pieceCode] || "pièce" : "case vide";
        const selectionLabel = square.coordinate === selectedSquare ? ", pièce sélectionnée" : "";
        const warningLabel = square.coordinate === lastWrongTarget ? ", dernière tentative incorrecte" : "";

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

    function toggleHint() {
        if (!(hintTextNode instanceof HTMLElement)) {
            return;
        }

        const hints = Array.isArray(puzzle.dammier_hints) ? puzzle.dammier_hints : [];
        hintTextNode.textContent = String(hints[stepIndex] || "Observe les lignes ouvertes, les échecs et les mats possibles.");
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
        if (startedAt !== null) {
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

        if (stepIndex >= puzzle.dammier_solution.length) {
            promptNode.textContent = "Puzzle terminé.";
            return;
        }

        promptNode.textContent = puzzle.dammier_solution.length === 1
            ? "Trouve le coup gagnant. Clique sur une pièce, puis sur sa case d'arrivée."
            : `Trouve le coup ${stepIndex + 1} sur ${puzzle.dammier_solution.length}. Clique sur une pièce, puis sur sa case d'arrivée.`;
    }

    function submitScore() {
        if (!isAuthenticated) {
            setFeedback("Puzzle résolu. Connecte-toi pour enregistrer ton score dans le classement.", "success");
            return;
        }

        const formData = new FormData();
        formData.append("action", "soumettre_resultat_dammier");
        formData.append("jeton_csrf", csrfToken);
        formData.append("dammier_puzzle_id", String(puzzle.dammier_id || ""));
        formData.append("dammier_week_key", String(puzzle.dammier_week_key || ""));
        formData.append("dammier_moves_count", String(movesCount));
        formData.append("dammier_elapsed_seconds", String(Math.max(1, getElapsedSeconds())));

        window.fetch(submitUrl, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: formData,
        })
            .then((response) => response.json())
            .then((result) => {
                if (!result?.success) {
                    setFeedback(result?.message || "Score non enregistré.", "error");
                    return;
                }

                setFeedback(result?.message || "Puzzle résolu. Ton score est enregistré dans le classement.", "success");
                renderRanking(Array.isArray(result.dammier_classement) ? result.dammier_classement : []);
            })
            .catch(() => {
                setFeedback("Le puzzle est résolu, mais l'enregistrement du score a échoué.", "error");
            });
    }

    function handleMove(move) {
        if (isSolved) {
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
                setFeedback("La réponse automatique du puzzle est invalide.", "error");
                return;
            }

            stepIndex += 1;
            renderBoard();
            syncSelectionText();
            focusSquare(String(move).slice(2, 4));
            if (stepIndex >= puzzle.dammier_solution.length) {
                isSolved = true;
                stopTimer();
                promptNode.textContent = "Bravo, le casse-tête est terminé.";
                clearHint();
                submitScore();
                return;
            }

            clearHint();
            setFeedback(
                automaticReply !== ""
                    ? `Bien joué. Réponse noire : ${formatMoveForDisplay(automaticReply)}. Trouve maintenant le coup suivant.`
                    : "Bien joué. Cherche maintenant le coup suivant.",
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
        if (isSolved) {
            return;
        }

        const pieceCode = String(boardState[coordinate] || "");

        if (selectedSquare === "") {
            if (!isOwnPiece(pieceCode)) {
                setFeedback("Sélectionne d'abord une pièce de ton camp.", "error");
                return;
            }

            startTimer();
            selectedSquare = coordinate;
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            focusSquare(coordinate);
            setFeedback("Pièce sélectionnée. Choisis maintenant sa destination.", "");
            return;
        }

        if (coordinate === selectedSquare) {
            selectedSquare = "";
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            focusSquare(coordinate);
            setFeedback("Sélection annulée.", "");
            return;
        }

        if (isOwnPiece(pieceCode)) {
            selectedSquare = coordinate;
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            focusSquare(coordinate);
            setFeedback("Pièce changée. Choisis sa destination.", "");
            return;
        }

        if (!isLegalMove(selectedSquare, coordinate, boardState, sideToMove)) {
            lastWrongTarget = coordinate;
            selectedSquare = "";
            renderBoard();
            syncSelectionText();
            focusSquare(coordinate);
            setFeedback("Ce coup est illégal selon les règles des échecs.", "error");
            return;
        }

        handleMove(`${selectedSquare}${coordinate}`);
    }

    function resetPuzzle() {
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
        setFeedback("Le score compte le nombre total de tentatives jusqu’à la résolution.", "");
        focusSquare(focusedSquare);
    }

    boardState = buildBoardState();
    renderBoard();
    renderRanking(Array.isArray(payload?.dammier_classement) ? payload.dammier_classement : []);
    renderStep();
    syncSelectionText();
    renderTimer();

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
