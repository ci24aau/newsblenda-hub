*** Begin Patch
*** Update File: includes/Earnings/Manager.php
@@
-        // Init reports manager if available
-        if ( file_exists( __DIR__ . '/../Reports/ReportsManager.php' ) ) {
-            require_once __DIR__ . '/../Reports/ReportsManager.php';
-            \Newsblenda\Editorial\Reports\Manager::init();
-        }
+        // Init reports manager if available
+        if ( file_exists( __DIR__ . '/../Reports/ReportsManager.php' ) ) {
+            require_once __DIR__ . '/../Reports/ReportsManager.php';
+            \Newsblenda\Editorial\Reports\Manager::init();
+        }
+        // Init notifications manager
+        if ( file_exists( __DIR__ . '/../Notifications/NotificationsManager.php' ) ) {
+            require_once __DIR__ . '/../Notifications/NotificationsManager.php';
+            require_once __DIR__ . '/../Notifications/Notifications.php';
+            \Newsblenda\Editorial\Notifications\Manager::init();
+        }
*** End Patch***
