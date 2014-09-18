<?php

/**
 * @file
 *   Policy commandfile. Modify as desired.
 *
 *   Validates commands as they are issued and returns an error
 *   or changes options when policy is violated.
 *
 *   You can copy this file to any of the following
 *     1. A .drush folder in your HOME folder.
 *     2. Anywhere in a folder tree below an active module on your site.
 *     3. /usr/share/drush/commands (configurable)
 *     4. In an arbitrary folder specified with the --include option.
 */

/**
 * Implementation of hook_drush_sql_sync_sanitize().
 */
function drush_policy_sql_sync_sanitize($source) {
  drush_sql_register_post_sync_op('drush_policy', dt('Reset passwords and email addresses in user table'), "UPDATE {users} SET pass = MD5('password'), mail = concat('user+', uid, '@example.com') WHERE uid > 0;");
}

/**
 * Implementation of drush_hook_COMMAND_validate().
 *
 * Prevent catastrophic rsync to live.
 */
function drush_policy_sql_sync_validate($source = NULL, $destination = NULL) {
  if ($destination == '@prod') {
    return drush_set_error(dt('Permission denied! You almost overwrote the production database you moron!'));
  }
}

/**
 * Implementation of drush_hook_COMMAND_validate().
 *
 * Prevent catastrophic rsync to live.
 */
function drush_policy_core_rsync_validate($source = NULL, $destination = NULL) {
  if ($destination == '@prod') {
    return drush_set_error(dt('Permission denied! You almost overwrote the production you moron!'));
  }
}

/**
 * Implementation of drush_hook_COMMAND_validate().
 *
 * Do not allow to clear the caches as root.
 */
function drush_policy_cache_clear_validate() {
  if (drush_is_windows()) {
    // $name = drush_get_username();
    // TODO: implement check for elevated process using w32api
    // as sudo is not available for Windows
    // http://php.net/manual/en/book.w32api.php
    // http://social.msdn.microsoft.com/Forums/en/clr/thread/0957c58c-b30b-4972-a319-015df11b427d
  }
  else {
    $name = posix_getpwuid(posix_geteuid());
    if ($name['name'] == 'root') {
      return drush_set_error('drush_policy', dt('Do not clear caches using root account.'));
    }
  }
}

/**
 * Support for clearing caches for Memcached, varnishd, OPCache, XCache, eAccelerator, etc.
 *
 * Implements drush_hook_post_COMMAND().
 *
 */
function drush_policy_post_cache_clear($type) {
  if ($type == 'all'):

    // Flush all cache from Memcache/Memcached.
    // See: https://www.drupal.org/node/2309657
    module_exists('memcache') &&
      memcache_invalidate_cache();

    // Flush all temporary cache from Varnish if used.
    // See: https://www.drupal.org/node/2309663
    module_exists('varnish') &&
      varnish_purge_all_pages();

    // Clear PHP OPCache cache if used.
    function_exists('opcache_reset') &&
      opcache_reset();

    // Clear PHP XCache cache if used.
    function_exists('xcache_clear_cache') &&
      xcache_clear_cache();

    // Clear PHP eAccelerator cache if used.
    function_exists('accelerator_reset') &&
      accelerator_reset();

    // Clear PHP APC cache if used.
    function_exists('apc_clear_cache') &&
      apc_clear_cache();

  endif;
}
