<? php
diff --git a/includes/bootstrap.inc b/includes/bootstrap.inc
index 2abc7f044b..4edf50e241 100644
--- a/includes/bootstrap.inc
+++ b/includes/bootstrap.inc
@@ -3780,8 +3780,12 @@ function _drupal_shutdown_function() {
   chdir(DRUPAL_ROOT);
 
   try {
-    while (list($key, $callback) = each($callbacks)) {
+    // Manually iterate over the array instead of using a foreach loop.
+    // A foreach operates on a copy of the array, so any shutdown functions that
+    // were added from other shutdown functions would never be called.
+    while ($callback = current($callbacks)) {
       call_user_func_array($callback['callback'], $callback['arguments']);
+      next($callbacks);
     }
   }
   catch (Exception $exception) {
diff --git a/includes/install.inc b/includes/install.inc
index 5e1d3c6326..b7db783586 100644
--- a/includes/install.inc
+++ b/includes/install.inc
@@ -779,7 +779,7 @@ function drupal_uninstall_modules($module_list = array(), $uninstall_dependents
     $module_list = array_flip(array_values($module_list));
 
     $profile = drupal_get_profile();
-    while (list($module) = each($module_list)) {
+    foreach (array_keys($module_list) as $module) {
       if (!isset($module_data[$module]) || drupal_get_installed_schema_version($module) == SCHEMA_UNINSTALLED) {
         // This module doesn't exist or is already uninstalled. Skip it.
         unset($module_list[$module]);
diff --git a/includes/menu.inc b/includes/menu.inc
index 4664d27e89..ca37ba509d 100644
--- a/includes/menu.inc
+++ b/includes/menu.inc
@@ -576,7 +576,8 @@ function _menu_load_objects(&$item, &$map) {
           // 'load arguments' in the hook_menu() entry, but they need
           // some processing. In this case the $function is the key to the
           // load_function array, and the value is the list of arguments.
-          list($function, $args) = each($function);
+          $args = current($function);
+          $function = key($function);
           $load_functions[$index] = $function;
 
           // Some arguments are placeholders for dynamic items to process.
@@ -2402,7 +2403,8 @@ function menu_set_active_trail($new_trail = NULL) {
       // a stripped down menu tree containing the active trail only, in case
       // the given menu has not been built in this request yet.
       $tree = menu_tree_page_data($preferred_link['menu_name'], NULL, TRUE);
-      list($key, $curr) = each($tree);
+      $curr = current($tree);
+      next($tree);
     }
     // There is no link for the current path.
     else {
@@ -2432,7 +2434,8 @@ function menu_set_active_trail($new_trail = NULL) {
         }
         $tree = $curr['below'] ? $curr['below'] : array();
       }
-      list($key, $curr) = each($tree);
+      $curr = current($tree);
+      next($tree);
     }
     // Make sure the current page is in the trail to build the page title, by
     // appending either the preferred link or the menu router item for the
diff --git a/includes/module.inc b/includes/module.inc
index 2e251080b7..4c2b3fbeeb 100644
--- a/includes/module.inc
+++ b/includes/module.inc
@@ -404,7 +404,11 @@ function module_enable($module_list, $enable_dependencies = TRUE) {
     // Create an associative array with weights as values.
     $module_list = array_flip(array_values($module_list));
 
-    while (list($module) = each($module_list)) {
+    // The array is iterated over manually (instead of using a foreach) because
+    // modules may be added to the list within the loop and we need to process
+    // them.
+    while ($module = key($module_list)) {
+      next($module_list);
       if (!isset($module_data[$module])) {
         // This module is not found in the filesystem, abort.
         return FALSE;
@@ -540,7 +544,11 @@ function module_disable($module_list, $disable_dependents = TRUE) {
     $module_list = array_flip(array_values($module_list));
 
     $profile = drupal_get_profile();
-    while (list($module) = each($module_list)) {
+    // The array is iterated over manually (instead of using a foreach) because
+    // modules may be added to the list within the loop and we need to process
+    // them.
+    while ($module = key($module_list)) {
+      next($module_list);
       if (!isset($module_data[$module]) || !$module_data[$module]->status) {
         // This module doesn't exist or is already disabled, skip it.
         unset($module_list[$module]);
diff --git a/modules/book/book.module b/modules/book/book.module
index 7afed9ae42..32047f93f5 100644
--- a/modules/book/book.module
+++ b/modules/book/book.module
@@ -768,11 +768,13 @@ function book_prev($book_link) {
     return NULL;
   }
   $flat = book_get_flat_menu($book_link);
-  // Assigning the array to $flat resets the array pointer for use with each().
+  reset($flat);
   $curr = NULL;
   do {
     $prev = $curr;
-    list($key, $curr) = each($flat);
+    $curr = current($flat);
+    $key = key($flat);
+    next($flat);
   } while ($key && $key != $book_link['mlid']);
 
   if ($key == $book_link['mlid']) {
@@ -806,9 +808,10 @@ function book_prev($book_link) {
  */
 function book_next($book_link) {
   $flat = book_get_flat_menu($book_link);
-  // Assigning the array to $flat resets the array pointer for use with each().
+  reset($flat);
   do {
-    list($key, $curr) = each($flat);
+    $key = key($flat);
+    next($flat);
   }
   while ($key && $key != $book_link['mlid']);
 
diff --git a/modules/locale/locale.test b/modules/locale/locale.test
index db87e05548..b890b06147 100644
--- a/modules/locale/locale.test
+++ b/modules/locale/locale.test
@@ -3188,11 +3188,7 @@ private function checkFixedLanguageTypes() {
     foreach (language_types_info() as $type => $info) {
       if (isset($info['fixed'])) {
         $negotiation = variable_get("language_negotiation_$type", array());
-        $equal = count($info['fixed']) == count($negotiation);
-        while ($equal && list($id) = each($negotiation)) {
-          list(, $info_id) = each($info['fixed']);
-          $equal = $info_id == $id;
-        }
+        $equal = array_keys($negotiation) === array_values($info['fixed']);
         $this->assertTrue($equal, format_string('language negotiation for %type is properly set up', array('%type' => $type)));
       }
     }
	 
	 commit 3e1079b25d715a3f3a602fff03aaf0ec84c90bed (HEAD -> 7.x-1.x)
Author: Liam Morland <lkmorlan@uwaterloo.ca>
Date:   Thu May 14 16:33:05 2020 -0400

    Fixes to #174

diff --git a/plugins/views/views_php_handler_field.inc b/plugins/views/views_php_handler_field.inc
index b7c7cc7..9c77432 100644
--- a/plugins/views/views_php_handler_field.inc
+++ b/plugins/views/views_php_handler_field.inc
@@ -220,7 +220,7 @@ class views_php_handler_field extends views_handler_field {
 
       $code = ' ?>' . $this->options['php_output'];
       $function = function($view, $handler, &$static, $row, $data, $value) use ($code) {
-        eval('?>' . $code);
+        eval($code);
       };
       ob_start();
       $function($this->view, $this, $this->php_static_variable, $normalized_row, $values, isset($values->{$this->field_alias}) ? $values->{$this->field_alias} : NULL);
diff --git a/plugins/views/views_php_handler_filter.inc b/plugins/views/views_php_handler_filter.inc
index d957eca..797d92b 100644
--- a/plugins/views/views_php_handler_filter.inc
+++ b/plugins/views/views_php_handler_filter.inc
@@ -65,7 +65,7 @@ class views_php_handler_filter extends views_handler_filter {
   function php_pre_execute() {
     // Ecexute static PHP code.
     if (!empty($this->options['php_setup'])) {
-      $code = $this->options['php_output'] . ';';
+      $code = $this->options['php_setup'] . ';';
       $function = function($view, $handler, &$static) use ($code) {
         eval($code);
       };
	   
	   
	   diff --git a/views_bulk_operations.module b/views_bulk_operations.module
index c62ffc9..081e540 100644
--- a/views_bulk_operations.module
+++ b/views_bulk_operations.module
@@ -196,7 +196,7 @@ function views_bulk_operations_get_operation_info($operation_id = NULL) {
       $operations += $plugin['list callback']();
     }
 
-    uasort($operations, create_function('$a, $b', 'return strcasecmp($a["label"], $b["label"]);'));
+    uasort($operations, '_views_bulk_operations_sort_operations_by_label');
   }
 
   if (!empty($operation_id)) {
@@ -207,6 +207,16 @@ function views_bulk_operations_get_operation_info($operation_id = NULL) {
   }
 }
 
+/**
+ * Sort function used by uasort in views_bulk_operations_get_operation_info().
+ *
+ * A closure would be better suited for this, but closure support was added in
+ * PHP 5.3 and D7 supports 5.2.
+ */
+function _views_bulk_operations_sort_operations_by_label($a, $b) {
+  return strcasecmp($a['label'], $b['label']);
+}
+
 /**
  * Returns an operation instance.
  *
  
  diff --git a/features.export.inc b/features.export.inc
index 5045b13..2a609e9 100644
--- a/features.export.inc
+++ b/features.export.inc
@@ -1088,13 +1088,6 @@ function _features_is_assoc($array) {
  *   returns a copy of the object or array with recursion removed
  */
 function features_remove_recursion($o) {
-  static $replace;
-  if (!isset($replace)) {
-    $replace = create_function(
-      '$m',
-      '$r="\x00{$m[1]}ecursion_features";return \'s:\'.strlen($r.$m[2]).\':"\'.$r.$m[2].\'";\';'
-    );
-  }
   if (is_array($o) || is_object($o)) {
     $re = '#(r|R):([0-9]+);#';
     $serialize = serialize($o);
@@ -1104,7 +1097,7 @@ function features_remove_recursion($o) {
         $chunk = substr($serialize, $last, $pos - $last);
         if (preg_match($re, $chunk)) {
           $length = strlen($chunk);
-          $chunk = preg_replace_callback($re, $replace, $chunk);
+          $chunk = preg_replace_callback($re, '_features_remove_recursion', $chunk);
           $serialize = substr($serialize, 0, $last) . $chunk . substr($serialize, $last + ($pos - $last));
           $pos += strlen($chunk) - $length;
         }
@@ -1114,13 +1107,21 @@ function features_remove_recursion($o) {
         $last += 4 + $length;
         $pos = $last;
       }
-      $serialize = substr($serialize, 0, $last) . preg_replace_callback($re, $replace, substr($serialize, $last));
+      $serialize = substr($serialize, 0, $last) . preg_replace_callback($re, '_features_remove_recursion', substr($serialize, $last));
       $o = unserialize($serialize);
     }
   }
   return $o;
 }
 
+/**
+ * Callback function for preg_replace_callback() to remove recursion.
+ */
+function _features_remove_recursion($m) {
+  $r = "\x00{$m[1]}ecursion_features";
+  return 's:' . strlen($r . $m[2]) . ':"' . $r . $m[2] . '";';
+}
+
 /**
  * Helper to removes a set of keys an object/array.
  *
diff --git a/includes/features.menu.inc b/includes/features.menu.inc
index edd4751..84af623 100644
--- a/includes/features.menu.inc
+++ b/includes/features.menu.inc
@@ -420,8 +420,12 @@ function features_menu_link_load($identifier) {
  * Returns a lowercase clean string with only letters, numbers and dashes
  */
 function features_clean_title($str) {
-  return strtolower(preg_replace_callback('/(\s)|([^a-zA-Z\-0-9])/i', create_function(
-          '$matches',
-          'return $matches[1]?"-":"";'
-      ), $str));
+  return strtolower(preg_replace_callback('/(\s)|([^a-zA-Z\-0-9])/i', '_features_clean_title', $str));
+}
+
+/**
+ * Callback function for preg_replace_callback() to clean a string.
+ */
+function _features_clean_title($matches) {
+  return $matches[1] ? '-' : '';
 }
 
 diff --git a/templates/system/misc/table.func.php b/templates/system/misc/table.func.php
index d4494c8..c127354 100644
--- a/templates/system/misc/table.func.php
+++ b/templates/system/misc/table.func.php
@@ -111,7 +111,7 @@ function wetkit_bootstrap_table($variables) {
   $responsive = $variables['responsive'];
 
   // Add sticky headers, if applicable.
-  if (count($header) && $sticky) {
+  if (!empty($header) && count($header) && $sticky) {
     drupal_add_js('misc/tableheader.js');
     // Add 'sticky-enabled' class to the table to identify it for JS.
     // This is needed to target tables constructed by this function.
	 
	 diff --git a/templates/system/misc/table.func.php b/templates/system/misc/table.func.php
index d4494c8..c127354 100644
--- a/templates/system/misc/table.func.php
+++ b/templates/system/misc/table.func.php
@@ -111,7 +111,7 @@ function wetkit_bootstrap_table($variables) {
   $responsive = $variables['responsive'];
 
   // Add sticky headers, if applicable.
-  if (count($header) && $sticky) {
+  if (!empty($header) && count($header) && $sticky) {
     drupal_add_js('misc/tableheader.js');
     // Add 'sticky-enabled' class to the table to identify it for JS.
     // This is needed to target tables constructed by this function.
	 
	 diff --git a/libraries.module b/libraries.module
index 62d3d79..63b5d1a 100644
--- a/libraries.module
+++ b/libraries.module
@@ -576,7 +576,7 @@ function libraries_detect($name = NULL) {
       $library['version'] = call_user_func_array($library['version callback'], array_merge(array($library), $library['version arguments']));
     }
     else {
-      $library['version'] = call_user_func($library['version callback'], $library, $library['version arguments']);
+      $library['version'] = call_user_func_array($library['version callback'], array(&$library, $library['version arguments']));
     }
     if (empty($library['version'])) {
       $library['error'] = 'not detected';
	   
	   
	     $responsive = $variables['responsive'];

  // Add sticky headers, if applicable.
  if (count($header) && $sticky) {
  if (!empty($header) && count($header) && $sticky) {
    drupal_add_js('misc/tableheader.js');
    // Add 'sticky-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
