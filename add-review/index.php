<?php
session_start();

$accessCode = 'Z026';
$dataFile   = __DIR__ . '/../reviews.json';
$message    = '';

function extractYouTubeId($url) {
    $url = trim($url);
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
        return $url;
    }
    return null;
}

function loadReviews($dataFile) {
    if (!file_exists($dataFile)) return [];
    $data = json_decode(file_get_contents($dataFile), true);
    return is_array($data) ? $data : [];
}

function saveReviews($dataFile, $reviews) {
    file_put_contents($dataFile, json_encode(array_values($reviews), JSON_PRETTY_PRINT));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'unlock') {
        if (($_POST['code'] ?? '') === $accessCode) {
            $_SESSION['review_access'] = true;
        } else {
            $message = 'Invalid access code.';
        }
    } elseif (!empty($_SESSION['review_access'])) {
        $reviews = loadReviews($dataFile);

        if ($action === 'add') {
            $youtubeId = extractYouTubeId($_POST['youtube_url'] ?? '');
            $name      = trim($_POST['name'] ?? '');
            if ($youtubeId) {
                $newId = 1;
                foreach ($reviews as $r) { $newId = max($newId, $r['id'] + 1); }
                $reviews[] = ['id' => $newId, 'youtubeId' => $youtubeId, 'name' => $name];
                saveReviews($dataFile, $reviews);
                $message = 'Review added successfully.';
            } else {
                $message = 'Could not read a valid YouTube video URL/ID.';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $reviews = array_filter($reviews, fn($r) => (int)$r['id'] !== $id);
            saveReviews($dataFile, $reviews);
            $message = 'Review deleted.';
        }
    } elseif ($action === 'logout') {
        unset($_SESSION['review_access']);
    }
}

$authed  = !empty($_SESSION['review_access']);
$reviews = loadReviews($dataFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Add Review | Smile Dental Care Clinic</title>
<link rel="icon" type="image/png" href="../icons/smile-logo.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    * { font-family: 'Poppins', sans-serif; }
    body { background: #f3f4f6; }
</style>
</head>
<body class="min-h-screen py-10 px-4">

<div class="max-w-lg mx-auto">
    <div class="text-center mb-6">
        <img src="../icons/smile-logo.png" alt="Smile Dental Care Clinic" class="h-12 mx-auto mb-2">
        <h1 class="text-xl font-extrabold text-[#09174b]">Video Reviews Admin</h1>
    </div>

    <?php if ($message): ?>
        <div class="bg-blue-50 border border-blue-200 text-[#09174b] text-sm font-semibold px-4 py-3 rounded-xl mb-5 text-center">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!$authed): ?>

        <form method="post" class="bg-white rounded-2xl shadow-lg p-6">
            <input type="hidden" name="action" value="unlock">
            <label class="block text-sm font-bold text-[#09174b] mb-2">Enter Access Code</label>
            <input type="password" name="code" required autofocus
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 mb-4 focus:outline-none focus:ring-2 focus:ring-[#09174b]"
                   placeholder="Access code">
            <button type="submit"
                    class="w-full bg-[#09174b] hover:bg-[#13287a] text-white font-bold py-2.5 rounded-xl transition">
                Unlock
            </button>
        </form>

    <?php else: ?>

        <form method="post" class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <input type="hidden" name="action" value="add">
            <label class="block text-sm font-bold text-[#09174b] mb-2">YouTube Video URL</label>
            <input type="text" name="youtube_url" required
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 mb-4 focus:outline-none focus:ring-2 focus:ring-[#09174b]"
                   placeholder="https://www.youtube.com/watch?v=...">

            <label class="block text-sm font-bold text-[#09174b] mb-2">Client Name (optional)</label>
            <input type="text" name="name"
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 mb-4 focus:outline-none focus:ring-2 focus:ring-[#09174b]"
                   placeholder="e.g. Ahmed Raza">

            <button type="submit"
                    class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2.5 rounded-xl transition">
                <i class="fas fa-plus"></i> Add Review
            </button>
        </form>

        <h2 class="text-sm font-bold text-[#09174b] mb-3 px-1">Current Reviews (<?= count($reviews) ?>)</h2>
        <div class="space-y-3">
            <?php foreach (array_reverse($reviews) as $r): ?>
                <div class="bg-white rounded-xl shadow p-3 flex items-center gap-3">
                    <img src="https://img.youtube.com/vi/<?= htmlspecialchars($r['youtubeId']) ?>/default.jpg"
                         alt="thumbnail" class="w-16 h-12 object-cover rounded-lg flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-[#09174b] truncate"><?= htmlspecialchars($r['name'] ?: 'Unnamed') ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($r['youtubeId']) ?></p>
                    </div>
                    <form method="post" onsubmit="return confirm('Delete this review?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                        <button type="submit" class="text-red-500 hover:text-red-700 px-2">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (empty($reviews)): ?>
                <p class="text-center text-gray-400 text-sm">No reviews added yet.</p>
            <?php endif; ?>
        </div>

        <form method="post" class="mt-6 text-center">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 underline">Lock this page</button>
        </form>

    <?php endif; ?>
</div>

</body>
</html>
