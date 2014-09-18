<?php

/**
 * @file
 *   drush rc configuration file.
 *
 * See `drush topic docs-bootstrap` for more information on how
 * bootstrapping affects the loading of drush configuration files.
 * See: `drush topic core-global-options` for other global drush options.
 */

/**
 * Useful shell aliases:
 *
 * Drush shell aliases act similar to git aliases.  For best results, define
 * aliases in one of the drushrc file locations between #3 through #6 above.
 * More information on shell aliases can be found via:
 * `drush topic docs-shell-aliases` on the command line.
 *
 * Notes:
 *   If 'HTTP request status' fails from CLI,
 *   check for valid hostname by: drush eval "print url('', array('absolute' => TRUE));"
 *
 * @see https://git.wiki.kernel.org/articles/a/l/i/Aliases.html#Advanced.
 */
$options['shell-aliases'] = array(

  /*
   * Deploy files on local (needs to be run locally)
   * Note: It works only when your current local environment works correctly.
   *
   */
  'deploy-local' => '!
    drush bb db manual &&
    drush --yes sql-sync @uat @self &&
    drush --yes rsync @uat:%files @self:%files &&
    drush --yes updb &&
    drush --yes fra &&
    drush --yes en example_devel &&
    drush cron &&
    drush status-report --severity=2 &&
    echo Deployment completed.
  ',

  /*
   * Deploy files on DEV (needs to be run locally)
   *
   */
  'deploy-dev' => '!
    drush @dev deploy-db &&
    drush @dev fix-git-perms && drush @dev deploy-code && drush @dev fix-git-perms &&
    drush @dev fix-files-perms && drush @dev deploy-files && drush @dev fix-files-perms &&
    drush @dev deploy-drupal
  ',

  /*
   * Deploy files on UAT (needs to be run locally)
   *
   */
  'deploy-uat' => '!
    drush @uat --yes archive-backup &&
    drush @dev deploy-uat &&
    drush @uat deploy-db &&
    drush @uat fix-git-perms && drush @uat deploy-code && drush @uat fix-git-perms &&
    drush @uat fix-files-perms && drush @uat deploy-files && drush @uat fix-files-perms &&
    drush @uat --yes en example_devel &&
    drush @uat deploy-drupal
  ',

  /*
   * Deploy files on Production (needs to be run locally)
  *
  */
  'deploy-prod' => '!
    drush --yes @prod archive-backup &&
    drush @uat deploy-prod &&
    drush @prod deploy-db &&
    drush @prod deploy-code &&
    drush @prod deploy-drupal
  ',

  /*
   * Refresh migrate settings
   *
   */
  'example-migrate-refresh' => '!drush eval "module_load_include(\'install\', \'example_migrate\'); example_migrate_install();" && drush migrate-register',

  /*
   * Example LDAP integration
   *
   */
  'example-ldap-enable' => 'en -y example_ldap ldap_user',
  'example-ldap-disable' => 'dis -y example_ldap ldap_user',

  /*
   * Example debugs
   *
   */
  'example-debug-enable' => '!
    drush vset --yes example_webstore_debug_level 3 \
    && drush vset --yes example_salesforce_debug_level 3 \
    && drush vset --yes rules_debug 2 \
    && drush vset --yes rules_debug_log 1 \
    && drush -y en example_devel
    && drush views-dev
    ',
  'example-debug-disable' => '!
    drush vset --yes example_webstore_debug_level 0 \
    && drush vset --yes example_salesforce_debug_level 0 \
    && drush vset --yes rules_debug 0 \
    && drush vset --yes rules_debug_log 0 \
    && drush -y dis example_devel
    ',

    /*
     * Re-calculate node comment statistics in Drupal 7.
     * See: https://drupal.org/node/137458#comment-5072066
     *
     */
    'fix-comment-count' => 'sqlq "
        TRUNCATE TABLE node_comment_statistics;
        INSERT INTO
            node_comment_statistics
        (
            nid,
            last_comment_timestamp,
            last_comment_name,
            last_comment_uid,
            comment_count
        )
        SELECT
            n.nid,
            IFNULL(last_comment.created,n.changed) AS last_comment_timestamp,
            IFNULL(last_comment.name,null) AS last_comment_name,
            IFNULL(last_comment.uid,n.uid) AS last_comment_uid,
            IFNULL(comment_count.comment_count,0) AS comment_count
        FROM
            node AS n
            LEFT OUTER JOIN (SELECT nid, COUNT(*) AS comment_count FROM comment WHERE status=1 GROUP BY nid) AS comment_count ON comment_count.nid=n.nid
            LEFT OUTER JOIN (SELECT nid, MAX(cid) AS max_cid FROM comment WHERE status=1 GROUP by nid) AS max_node_comment ON max_node_comment.nid=n.nid
            LEFT OUTER JOIN (SELECT cid,uid,name,created FROM comment ORDER BY cid DESC LIMIT 1) AS last_comment ON last_comment.cid=max_node_comment.max_cid
        WHERE
            n.status=1
        ORDER BY
            n.nid;
      "',

  /*
   * Search for broken entity types.
   *
   */
  // 'broken-entities' => "!drush eval 'foreach (entity_get_info() as $entity_type => $entity_info) { empty($entity_info[label]) && var_dump($entity_type, $entity_info); };'",

  /*
   * SQL Stats
   *
   */
  'sql-stat' => 'sqlq "SHOW ENGINE INNODB STATUS\G"',

);

// Control automatically check for updates in pm-updatecode and drush version.
// FALSE = never check for updates.  'head' = allow updates to drush-HEAD.
// TRUE (default) = allow updates to latest stable release.
$options['self-update'] = FALSE;

// Enable verbose mode.
// $options['v'] = TRUE;

/*
 * An array of aliases for common rsync targets.
 */
$options['path-aliases'] = array(
  '%files'   => 'sites/default/files',
  '%private' => 'sites/default/private',
);

// Default logging level for php notices.  Defaults to "notice"; set to "warning"
// if doing drush development.  Also make sure that error_reporting is set to E_ALL
// in your php configuration file.  See 'drush status' for the path to your php.ini file.
# $options['php-notices'] = 'warning';

/*
 * Customize this associative array with your own tables. This is the list of
 * tables whose *data* is skipped by the 'sql-dump' and 'sql-sync' commands when
 * a structure-tables-key is provided. You may add new tables to the existing
 * array or add a new element.
 */
$options['structure-tables'] = array(
 'common' => array('cache', 'cache_filter', 'cache_menu', 'cache_page', 'history', 'search_index', 'sessions', 'watchdog'),
);

/**
 * List of tables to be omitted entirely from SQL dumps made by the 'sql-dump'
 * and 'sql-sync' commands when the "--skip-tables-key=common" option is
 * provided on the command line.  This is useful if your database contains
 * non-Drupal tables used by some other application or during a migration for
 * example.  You may add new tables to the existing array or add a new element.
 */
$options['skip-tables'] = array(
 'common' => array('field_deleted_revision_63', 'field_deleted_revision_62', 'field_deleted_revision_60', 'field_deleted_data_60', 'field_deleted_data_63', 'field_deleted_revision_61', 'field_deleted_data_62', 'field_deleted_data_61', 'field_deleted_data_59', 'field_deleted_revision_59'),
);

/**
 * Specify options to pass to ssh in backend invoke.  The default is to prohibit
 * password authentication, and is included here, so you may add additional
 * parameters without losing the default configuration.
 */
# $options['ssh-options'] = '-o PasswordAuthentication=no -F scripts/example/conf/ssh/config';

/*
 * Command-specific options
 *
 * To define options that are only applicable to certain commands,
 * make an entry in the 'command-specific' structures as shown below.
 * The name of the command may be either the command's full name
 * or any of the command's aliases.
 *
 * Options defined here will be overridden by options of the same
 * name on the command line.  Unary flags such as "--verbose" are overridden
 * via special "--no-xxx" options (e.g. "--no-verbose").
 *
 * Limitation: If 'verbose' is set in a command-specific option,
 * it must be cleared by '--no-verbose', not '--no-v', and visa-versa.
 */
$command_specific['rsync'] = array('mode' => 'rlptzO', 'verbose' => TRUE, 'no-perms' => TRUE, 'exclude' => '*.gz');
$command_specific['archive-dump'] = array('verbose' => TRUE);
$command_specific['sql-sync'] = array('verbose' => TRUE, 'sanitize' => TRUE, 'create-db' => TRUE, 'cache' => TRUE, 'structure-tables-key' => 'common', 'skip-tables-key' => 'common');
// $command_specific['sql-sync']['sanitize'] = FALSE; // Disable sanitize option for sql-sync.
$command_specific['sql-dump'] = array('ordered-dump' => TRUE, 'structure-tables-key' => 'common', 'skip-tables-key' => 'common');
$command_specific['migrate-import'] = array('instrument' => FALSE, 'feedback' => '60 seconds');

// Always show release notes when running pm-update or pm-updatecode
$command_specific['pm-update'] = array('notes' => TRUE);
$command_specific['pm-updatecode'] = array('notes' => TRUE);

/**
 * PHP variable overrides:
 */
ini_set('memory_limit', '1G');

/**
 * Variable overrides:
 *
 * To override specific entries in the 'variable' table for this site,
 * set them here. Any configuration setting from the 'variable'
 * table can be given a new value. We use the $override global here
 * to make sure that changes from settings.php can not wipe out these
 * settings.
 *
 * Remove the leading hash signs to enable.
 */
$override = array(
  'example_debug_level' => 2,
);

