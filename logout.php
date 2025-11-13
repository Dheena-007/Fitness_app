<?php
// --- logout.php ---

// Session-ஐத் தொடங்குதல்
session_start();

// அனைத்து session மாறிகளையும் நீக்குதல்
session_unset();

// Session-ஐ முழுமையாக அழித்தல்
session_destroy();

// முக்கிய மாற்றம்: login.php என்பதற்கு பதிலாக index.php பக்கத்திற்கு அனுப்புதல்
header("Location: index.php");

// ஸ்கிரிப்ட்டை நிறுத்துதல்
exit();
?>