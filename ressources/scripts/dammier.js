/**
 * dammier.js
 *
 * Moteur du casse-tete hebdomadaire:
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
    const files = ["a", "b", "c", "d", "e", "f", "g", "h"];
    const sideToMove = String(puzzle.dammier_side_to_move || "w");
    const opponentSide = sideToMove === "w" ? "b" : "w";
    const replySequence = Array.isArray(puzzle.dammier_replies) ? puzzle.dammier_replies : [];
    let stepIndex = 0;
    let movesCount = 0;
    let startedAt = Date.now();
    let timerId = null;
    let isSolved = false;
    let selectedSquare = "";
    let boardState = {};
    let lastWrongTarget = "";

    function formatElapsed(seconds) {
        const safeSeconds = Math.max(0, seconds);
        const minutes = String(Math.floor(safeSeconds / 60)).padStart(2, "0");
        const remainder = String(safeSeconds % 60).padStart(2, "0");

        return `${minutes}:${remainder}`;
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

        selectionNode.textContent = `Piece selectionnee: ${selectedSquare}. Choisis la case d'arrivee.`;
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
            squareNode.setAttribute("aria-label", `Case ${square.coordinate}`);
            squareNode.setAttribute("data-dammier-file", row === 7 ? square.coordinate.charAt(0) : "");
            squareNode.setAttribute("data-dammier-rank", column === 0 ? square.coordinate.charAt(1) : "");
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
                handleSquareClick(square.coordinate);
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
    }

    function toggleHint() {
        if (!(hintTextNode instanceof HTMLElement)) {
            return;
        }

        const hints = Array.isArray(puzzle.dammier_hints) ? puzzle.dammier_hints : [];
        hintTextNode.textContent = String(hints[stepIndex] || "Observe les lignes ouvertes, les echecs et les mats possibles.");
        hintTextNode.hidden = !hintTextNode.hidden;
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
        stopTimer();

        const tick = () => {
            if (timerNode instanceof HTMLElement) {
                timerNode.textContent = formatElapsed(Math.floor((Date.now() - startedAt) / 1000));
            }
        };

        tick();
        timerId = window.setInterval(tick, 1000);
    }

    function renderStep() {
        if (!(promptNode instanceof HTMLElement)) {
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
            setFeedback("Puzzle resolu. Connecte-toi pour enregistrer ton score dans le classement.", "success");
            return;
        }

        const formData = new FormData();
        formData.append("action", "soumettre_resultat_dammier");
        formData.append("jeton_csrf", csrfToken);
        formData.append("dammier_puzzle_id", String(puzzle.dammier_id || ""));
        formData.append("dammier_week_key", String(puzzle.dammier_week_key || ""));
        formData.append("dammier_moves_count", String(movesCount));
        formData.append("dammier_elapsed_seconds", String(Math.max(1, Math.floor((Date.now() - startedAt) / 1000))));

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
                    setFeedback(result?.message || "Score non enregistre.", "error");
                    return;
                }

                setFeedback("Puzzle resolu. Ton score est enregistre dans le classement.", "success");
                renderRanking(Array.isArray(result.dammier_classement) ? result.dammier_classement : []);
            })
            .catch(() => {
                setFeedback("Le puzzle est resolu, mais l'enregistrement du score a echoue.", "error");
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
                setFeedback("La reponse automatique du puzzle est invalide.", "error");
                return;
            }

            stepIndex += 1;
            renderBoard();
            syncSelectionText();
            if (stepIndex >= puzzle.dammier_solution.length) {
                isSolved = true;
                stopTimer();
                promptNode.textContent = "Bravo, le casse-tete est termine.";
                clearHint();
                submitScore();
                return;
            }

            clearHint();
            setFeedback(
                automaticReply !== ""
                    ? `Bien joue. Reponse noire: ${formatMoveForDisplay(automaticReply)}. Trouve maintenant le coup suivant.`
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
        setFeedback("Ce n'est pas le bon coup. Le score ajoute une tentative.", "error");
    }

    function handleSquareClick(coordinate) {
        if (isSolved) {
            return;
        }

        const pieceCode = String(boardState[coordinate] || "");

        if (selectedSquare === "") {
            if (!isOwnPiece(pieceCode)) {
                setFeedback("Selectionne d'abord une piece de ton camp.", "error");
                return;
            }

            selectedSquare = coordinate;
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            setFeedback("Piece selectionnee. Choisis maintenant sa destination.", "");
            return;
        }

        if (coordinate === selectedSquare) {
            selectedSquare = "";
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            setFeedback("Selection annulee.", "");
            return;
        }

        if (isOwnPiece(pieceCode)) {
            selectedSquare = coordinate;
            lastWrongTarget = "";
            renderBoard();
            syncSelectionText();
            setFeedback("Piece changee. Choisis sa destination.", "");
            return;
        }

        if (!isLegalMove(selectedSquare, coordinate, boardState, sideToMove)) {
            lastWrongTarget = coordinate;
            selectedSquare = "";
            renderBoard();
            syncSelectionText();
            setFeedback("Ce coup est illegal selon les regles des echecs.", "error");
            return;
        }

        handleMove(`${selectedSquare}${coordinate}`);
    }

    function resetPuzzle() {
        stepIndex = 0;
        movesCount = 0;
        startedAt = Date.now();
        isSolved = false;
        selectedSquare = "";
        lastWrongTarget = "";
        boardState = buildBoardState();
        renderBoard();
        renderStep();
        syncSelectionText();
        clearHint();
        setFeedback("Le score compte le nombre total de tentatives jusqu'a la resolution.", "");
        startTimer();
    }

    boardState = buildBoardState();
    renderBoard();
    renderRanking(Array.isArray(payload?.dammier_classement) ? payload.dammier_classement : []);
    renderStep();
    syncSelectionText();
    startTimer();

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
