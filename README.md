# টাকার হিসাব — হোস্টিং ফোল্ডার

এই ফোল্ডারের **ভেতরের সব ফাইল** হোস্টিংয়ের `htdocs` এ আপলোড করুন।

| ফোল্ডার/ফাইল | কাজ |
|---|---|
| `index.html`, `assets/`, `sw.js`, `manifest.webmanifest`, আইকন | অ্যাপ (build করার পর আসে) |
| `admin/` | অ্যাডমিন প্যানেল (`/admin/`) |
| `api/` | `config.php` (পাবলিক সেটিংস), `backup.php` (ব্যাকআপ নেওয়া), `health.php` |
| `includes/` | PHP কোর (ব্রাউজার থেকে বন্ধ) |
| `database/` | `schema.sql` (ব্রাউজার থেকে বন্ধ) |
| `uploads/backups/` | ব্যাকআপ JSON (ব্রাউজার থেকে বন্ধ) |
| `install.php` | একবার চালানোর ইনস্টলার — **চালানোর পর মুছে ফেলুন** |
| `.htaccess` | ডিরেক্টরি লিস্টিং বন্ধ, নিরাপত্তা, ক্যাশ |

প্রয়োজন: PHP ৭.৪+, MySQL, Apache/LiteSpeed (`.htaccess` সাপোর্ট)।
