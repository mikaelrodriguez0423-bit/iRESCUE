<?php
require_once '../Core/config.php';
$accounts = [
    ['username' => 'admin',      'password' => 'Admin@2025'],
    ['username' => 'responder1', 'password' => 'Responder@2025'],
];

foreach ($accounts as $acc) {
    $hash = password_hash($acc['password'], PASSWORD_BCRYPT, ['cost' => 10]);
    $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param('ss', $hash, $acc['username']);
    $stmt->execute();
    echo "✅ Password reset for: " . $acc['username'] . "<br>";
    $stmt->close();
}

echo "<br><strong>Done! Delete this file immediately.</strong>";
?>
```

**Step 2 — Visit it once in your browser:**
```
http://localhost/your-project/reset_pass.php

username: admin
password: Admin@2025


username: responder1
password: Responder@2025