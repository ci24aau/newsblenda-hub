*** Begin Patch
*** Update File: includes/Earnings/Manager.php
@@
     public static function setup() {
         self::maybe_create_tables();
         // Track single post views
         add_action( 'template_redirect', [ __CLASS__, 'maybe_track_view' ] );
         // Cron hook
         add_action( 'nbe_daily_earnings', [ __CLASS__, 'run_daily_earnings' ] );
         // Ensure cron scheduled
@@
         add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
+        // Init reports manager if available
+        if ( file_exists( __DIR__ . '/../Reports/ReportsManager.php' ) ) {
+            require_once __DIR__ . '/../Reports/ReportsManager.php';
+            \Newsblenda\Editorial\Reports\Manager::init();
+        }
     }
*** End Patch***
