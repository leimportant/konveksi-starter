<!DOCTYPE html>
<html>
<head>
    <title>Pesan Baru</title>
</head>
<body>
    <h2>Pesan Baru dari {{ ucfirst($chatMessage->sender_type) }}</h2>
    <p><strong>Pesan:</strong> {{ $chatMessage->message }}</p>
    <p><em>Dikirim pada: {{ $chatMessage->created_at->format('d-m-Y H:i') }}</em></p>
</body>
</html>
