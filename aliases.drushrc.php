<?php

/**
 * @file
 *   Alias file for drush command line tool.
 *
 * There are several ways to create alias files:
 *  - Put multiple aliases in a single file called aliases.drushrc.php
 *  - Put each alias in a separate file called ALIASNAME.alias.drushrc.php
 *  - Put groups of aliases into files called GROUPNAME.aliases.drushrc.php
 *
 * See:
 *  http://drush.ws/docs/shellaliases.html
 *  $ drush topic docs-aliases
 *
 *  Example usage:
 *    drush @dev status
 *    drush @uat status
 *    drush @dev deploy-code
 *
 */

/**
 * Alias for Development environment run on Drupal 7.
 *
 */
$aliases['global'] = array(
    // 'ssh-options' => "-p 1022 -F %root/scripts/example/conf/ssh/config" . DRUPAL_ROOT,
    'path-aliases' => array(
      '%files'   => 'sites/default/files',
      '%private' => 'sites/default/private/files',
    ),

    // These options will only be set if the alias is used with the specified command.
    'command-specific' => array(
      'sql-sync' => array(
        'cache' => TRUE,
        'create-db' => TRUE,
        'sanitize' => TRUE,
        'ordered-dump' => FALSE,
        'structure-tables-key' => 'common',
        'skip-tables-key' => 'common',
        'structure-tables' => array(
          // You can add more tables which contain data to be ignored by the database dump
          'common' => array('cache', 'cache_filter', 'cache_menu', 'cache_page', 'history', 'search_index', 'sessions', 'watchdog'),
        ),
        'skip-tables' => array(
          'common' => array('field_deleted_revision_63', 'field_deleted_revision_62', 'field_deleted_revision_60', 'field_deleted_data_60', 'field_deleted_data_63', 'field_deleted_revision_61', 'field_deleted_data_62', 'field_deleted_data_61', 'field_deleted_data_59', 'field_deleted_revision_59'),
        ),
      ),
      'sql-dump' => array(
        'ordered-dump' => TRUE,
        'structure-tables-key' => 'common',
        'skip-tables-key' => 'common',
      ),
      'rsync' => array(
          'mode' => 'rlptzO', // Single-letter rsync options are placed in the 'mode' key instead of adding '--mode=rultvz' to drush rsync command.
          'verbose' => TRUE,
          'no-perms' => TRUE,
          'exclude' => "'*.gz'", // Wrapping an option's value in "" preserves inner '' on output, but is not always required.
        # 'exclude-from' => "'/etc/rsync/exclude.rules'", // If you need multiple exludes, use an rsync exclude file.
          'ssh-options' => '-F config',
          'filter' => "'exclude *.sql'", // Filter options with white space must be wrapped in "" to preserve the inner ''.
        # 'filter' => "'merge /etc/rsync/default.rules'", // If you need multple filter options, see rsync merge-file options.
          ),
    ), // end: command-specific

    // Applied only if the alias is used as the source.
    'source-command-specific' => array(
    ),

    // Applied only if the alias is used as the target.
    'target-command-specific' => array(
    ),

);

/**
 * Alias for Development environments run on Drupal 7.
 *
 */
$aliases['test'] = array(
  'invoke-multiple' => TRUE,
  'site-list' => array(
    '@dev',
    '@uat',
  ),
);

/**
 * Alias for Development environment run on Drupal 7.
 *
 */
$aliases['dev'] = array(
    'uri' => 'www-dev.example.com',
    'root' => '/var/www',
    'parent' => '@global',
    'remote-host' => 'dev',
    // 'remote-user' => 'publisher',

    /*
    'options' => array(
      'ssh-options' => 'BLAH -o PasswordAuthentication=no -F scripts/example/conf/ssh/config',
    ),
    */

    'variables' => array('mail_system' => array('default-system' => 'DevelMailLog')),

    # This shell alias will run `mycommand` when executed via `drush @stage site-specific-alias`
    'shell-aliases' => array(

      /*
       * Deploy code on DEV from git dev branch
       *
       */
      'deploy-code' => '!git fetch origin && sudo git stash && sudo git reset origin/dev --hard',

      /*
       * Deploy database on DEV from UAT
       *
       */
      'deploy-db' => '!
        sudo -uwww-data drush --watchdog=print bb db manual &&
        drush sql-sync --yes @uat @self
      ',

      /*
       * Deploy files on DEV from UAT
       *
       *  Notes:
       *    origin git@git:~git/exampledotcom
       *   'deploy-files' => '!git fetch origin master && sudo git stash && sudo git reset origin/master --hard && sh ./scripts/example/deploy.sh',
       *    E.g. drush sql-sync @prod default --dump-dir=/tmp --structure-tables-key=common --sanitize
       *    See: http://shomeya.com/articles/using-per-project-drush-commands-to-simplify-your-development
       *
       *  Requirements:
       *    sudo chown -R www-data:root /var/www/sites/default/files /var/www/sites/default/private
       *    sudo chmod ug+w /var/www/sites/default/files /var/www/sites/default/private
       */
      'deploy-files' => '!drush --yes rsync @uat:%files @self:%files',
        // sudo -uwww-data drush @uat status
        // echo sudo chown -R %apache:root %files %private

      /*
       * Drupal deployment on UAT
       *
       * Permission requirements:
       *   sudo chown -R root:root /var/www
       *   sudo chown -R www-data:root /var/www/sites/default/private /var/www/sites/default/files
       */
      'deploy-drupal' => "!
        sudo -uwww-data drush status &&
        sudo -uwww-data drush --watchdog=print bb db manual &&
        sudo -uwww-data drush --watchdog=print -y updb &&
        sudo -uwww-data drush --watchdog=print cc all &&
        sudo -uwww-data drush --watchdog=print -y fra &&
        sudo -uwww-data drush --watchdog=print cron &&
        sudo -uwww-data drush status-report --severity=2 &&
        echo Deployment completed.
      ",

      /*
       * Deploy code from DEV into UAT
       *
       * Troubleshooting:
       *  - In case of error like 'remote: error: refusing to update checked out branch: refs/heads/master'
       *    on UAT you've to run: git config receive.denyCurrentBranch ignore
       */
      'deploy-uat' => '!git push uat dev master --force',

      /*
       * Fix git permissions to support multiple user git deployment.
       *
       */
      'fix-git-perms' => "!
        sudo chown -R root:root .git &&
        sudo chmod -R ug+w .git &&
        git config core.sharedRepository group
      ",

      /*
       * Fix files permissions to support multiple user rsync deployment.
       *
       */
      'fix-files-perms' => "!
        sudo chown -R www-data:root sites/default/private sites/default/files &&
        sudo chmod -R ug+w sites/default/private sites/default/files
      ",

      /*
       * General deployment to DEV
       * FIXME: Ideally it should be: 'deploy' => 'deploy-db && deploy-code && deploy-files && deploy-drupal'
       *
       */
      'deploy' => 'deploy-code',
    ),
);

/**
 * Alias for UAT environment run on Drupal 7.
 *
 */
$aliases['uat'] = array(
    'uri' => 'www-uat.example.com',
    'root' => '/var/www',
    'parent' => '@global',
    'remote-host' => 'uat',
    // 'remote-user' => 'publisher',
    'ssh-options' => "-p 1022",

    'command-specific' => array (
      'sql-sync' => array (
        'sanitize' => TRUE,
        'no-ordered-dump' => TRUE,
        'structure-tables' => array(
        // You can add more tables which contain data to be ignored by the database dump
          'common' => array('cache', 'cache_filter', 'cache_menu', 'cache_page', 'history', 'sessions', 'watchdog'),
        ),
      ),
      'rsync' => array('mode' => 'rlptzO', 'verbose' => TRUE, 'no-perms' => TRUE, 'exclude' => '*.gz'),
    ),

    # This shell alias will run `mycommand` when executed via `drush @stage site-specific-alias`
    'shell-aliases' => array(

      /*
       * Deploy code on UAT from existing git dev
       *
       * Note:
       * Deployment is done by pushing branch from DEV into UAT.
       * So please use: drush @dev deploy-uat before doing deploy.
       *
       *
       */
      'deploy-code' => '!git push . dev:master && sudo git stash && sudo git reset HEAD --hard && ./scripts/example/git/git-deploy-tag.sh UAT',

      /*
       * Deploy database on UAT from Production
      *
      */
      'deploy-db' => '!
        sudo -uwww-data drush --watchdog=print bb db manual &&
        drush sql-sync --yes @prod @self
      ',

      /*
       * Deploy files on UAT from Prod.
       *
       */
      'deploy-files' => '!drush --yes rsync @prod:%files @self:%files',

      /*
       * Drupal deployment on UAT
       *
       * Permission requirements:
       *   sudo chown -R root:root /var/www
       *   sudo chown -R www-data:root /var/www/sites/default/private /var/www/sites/default/files
       */
      'deploy-drupal' => "!
        sudo -u www-data www-data drush status &&
        sudo -u www-data www-data drush --watchdog=print bb db manual &&
        sudo -u www-data www-data drush --watchdog=print -y updb &&
        sudo -u www-data www-data drush --watchdog=print cc all &&
        sudo -u www-data www-data drush --watchdog=print -y fra &&
        sudo -u www-data www-data drush --watchdog=print cron &&
        sudo -u www-data www-data drush status-report --severity=2 &&
        echo Deployment completed.
      ",

      /*
       * Deploy code from UAT into PROD
       *
       * Troubleshooting:
       *  - In case of error like 'remote: error: refusing to update checked out branch: refs/heads/master'
       *    on PROD you've to run: git config receive.denyCurrentBranch ignore
       */
      'deploy-prod' => '!git push prod master --force',

      /*
       * Fix git permissions to support multiple user git deployment.
       *
       */
      'fix-git-perms' => "!
        sudo chown -R root:root .git &&
        sudo chmod -R ug+w .git &&
        git config core.sharedRepository group
      ",

      /*
       * Fix files permissions to support multiple user rsync deployment.
       *
       */
      'fix-files-perms' => "!
        sudo chown -R www-data:root sites/default/private sites/default/files &&
        sudo chmod -R ug+w sites/default/private sites/default/files
      ",

      /*
       * General deployment to DEV
       * FIXME: Ideally it should be: 'deploy' => 'deploy-db && deploy-code && deploy-files && deploy-drupal'
       *
       */
      'deploy' => 'deploy-code',
    ),
);

/**
 * Alias for Production environment run on Drupal 7.
 *
 */
$aliases['prod'] = array(
    'uri' => 'www-prod.example.com',
    'root' => '/var/www',
    'remote-host' => 'prod',
    // 'remote-user' => 'publisher',
    'ssh-options' => "-p 1022",
    'path-aliases' => array(
      '%files'   => 'sites/default/files',
      '%private' => 'sites/default/private',
     ),

    'command-specific' => array (
      'sql-sync' => array (
        'sanitize' => TRUE,
        'structure-tables' => array(
        // You can add more tables which contain data to be ignored by the database dump
          'common' => array('cache', 'cache_filter', 'cache_menu', 'cache_page', 'history', 'sessions', 'watchdog'),
        ),
      ),
      'sql-dump' => array (
        'ordered-dump' => TRUE,
      ),
    ),

    # This shell alias will run `mycommand` when executed via `drush @stage site-specific-alias`
    'shell-aliases' => array(

      /*
       * Deploy code on PROD from existing git master
       *
       * Note:
       * Deployment is done by pushing branch from DEV into UAT.
       * So please use: drush @dev deploy-uat before doing deploy.
       *
       *
       */
      'deploy-code' => '!sudo git stash && sudo git reset HEAD --hard && ./scripts/example/git/git-deploy-tag.sh Prod',

      /*
       * Deploy database on Production (just do the backup).
       *
       */
      'deploy-db' => '!
        sudo -uwww-data drush --watchdog=print bb db manual
      ',

      /*
       * Drupal deployment on PROD
       *
       * Permission requirements:
       *   sudo chown -R root:root /var/www
       *   sudo chown -R www-data:root /var/www/sites/default/private /var/www/sites/default/files
       */
      'deploy-drupal' => "!
        sudo -u www-data www-data drush status &&
        sudo -u www-data www-data drush --watchdog=print bb db manual &&
        sudo -u www-data www-data drush --watchdog=print -y updb &&
        sudo -u www-data www-data drush --watchdog=print cc all &&
        sudo -u www-data www-data drush --watchdog=print -y fra &&
        sudo -u www-data www-data drush --watchdog=print cron &&
        sudo -u www-data www-data drush status-report --severity=2 &&
        echo Deployment completed.
      ",

      /*
       * Fix git permissions to support multiple user git deployment.
       *
       */
      'fix-git-perms' => "!
        sudo chown -R root:root .git &&
        sudo chmod -R ug+w .git &&
        git config core.sharedRepository group
      ",

      /*
       * General deployment to PROD
       * FIXME: Ideally it should be: 'deploy' => 'deploy-db && deploy-code && deploy-files && deploy-drupal'
       *
       */
      'deploy' => 'deploy-code',
    ),
);


/**
 * Alias for Development environment for Drupal 6.
 *
 */
$aliases['dev6'] = array(
    'uri' => 'dev6',
    'root' => '/var/www/drupal',
    'remote-host' => 'example-dev',
    'ssh-options' => "-p 1022",
    'path-aliases' => array(
      '%files'   => 'sites/default/files',
      '%private' => 'sites/default/files/private',
     ),

    'command-specific' => array (
      'sql-sync' => array (
        'sanitize' => TRUE,
        'no-ordered-dump' => TRUE,
        'structure-tables' => array(
        // You can add more tables which contain data to be ignored by the database dump
          'common' => array('cache', 'cache_filter', 'cache_menu', 'cache_page', 'history', 'sessions', 'watchdog'),
        ),
      ),
      'rsync' => array('mode' => 'rlptzO', 'verbose' => TRUE, 'no-perms' => TRUE, 'exclude' => '*.gz'),
    ),
);

