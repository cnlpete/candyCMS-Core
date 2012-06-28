{strip}
  <!DOCTYPE html>
  <html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'
        xmlns:og='http://opengraphprotocol.org/schema/'
        xmlns:fb='http://www.facebook.com/2008/fbml'>
    <head>
      {* Production mode: Use compiled CSS *}
      <link href='{$_PATH.css}/core/application{$_SYSTEM.compress_files_suffix}.css'
            rel='stylesheet' type='text/css' media='screen, projection'/>

      <meta http-equiv='content-type' content='text/html;charset=utf-8'/>
      <meta name='description' content='{$_WEBSITE.meta.description}'/>
      <meta name='keywords' content='{$_WEBSITE.meta.keywords}'/>
      <meta name='dc.title' content='{$_WEBSITE.title}'/>

      {* Provide more details for specific entry. *}
      {if isset($_REQUEST.id)}
        <meta property='og:description' content='{$_WEBSITE.meta.og.description}'/>
        <meta property='og:site_name' content='{$_WEBSITE.meta.og.site_name}'/>
        <meta property='og:title' content='{$_WEBSITE.meta.og.title}'/>
        <meta property='og:url' content='{$_WEBSITE.meta.og.url}'/>
        <meta itemprop='name' content='{$_WEBSITE.meta.og.title}'>
        <meta itemprop='description' content='{$_WEBSITE.meta.og.description}'>
      {/if}

      {* If we want to use a facebook plugin, provide tracking data. *}
      {if $_SYSTEM.facebook_plugin == true}
        <meta property='fb:admins' content='{$PLUGIN_FACEBOOK_ADMIN_ID}'/>
        <meta property='fb:app_id' content='{$PLUGIN_FACEBOOK_APP_ID}'/>
      {/if}

      {* Basic stuff *}
      <link href='/rss' rel='alternate' type='application/rss+xml' title='RSS'/>
      <link href='{$_PATH.public}/favicon.ico' rel='shortcut icon' type='image/x-icon'/>

      {* Include jQuery and its components *}
      <script type='text/javascript' src='http://code.jquery.com/jquery-1.7.1{$_SYSTEM.compress_files_suffix}.js'></script>

      {* Fallback if CDN is not available. Also include language parts. *}
      <script type='text/javascript'>
        if (typeof jQuery == 'undefined')
          document.write(unescape("%3Cscript src='{$_PATH.js}/core/jquery.1.7.1{$_SYSTEM.compress_files_suffix}.js' type='text/javascript'%3E%3C/script%3E"));

        var lang = {$_SYSTEM.json_language};
      </script>

      <title>{$_WEBSITE.title} - {$WEBSITE_NAME}</title>
    </head>
    <!--[if lt IE 7]><body class='ie6'><![endif]-->
    <!--[if IE 7]><body class='ie7'><![endif]-->
    <!--[if IE 8]><body class='ie8'><![endif]-->
    <!--[if gt IE 8]><!--><body><!--<![endif]-->

      {* Top navigation *}
      <nav class='navbar navbar-fixed-top'>
        <div class='navbar-inner'>
          <div class='container'>
            <a href='/' class='brand' title='{$WEBSITE_NAME}'>
              {$WEBSITE_NAME}
              {if $WEBSITE_MODE !== 'production'}
                &nbsp;- {$WEBSITE_MODE|upper}
              {/if}
            </a>
            <ul class='nav'>
              <li{if $_REQUEST.controller == 'blogs'} class='active'{/if}>
                <a href='/blogs'>{$lang.global.blog}</a>
              </li>
              <li{if $_REQUEST.controller == 'galleries'} class='active'{/if}>
                <a href='/galleries'>{$lang.global.gallery}</a>
              </li>
              <li{if $_REQUEST.controller == 'calendars'} class='active'{/if}>
                <a href='/calendars'>{$lang.global.calendar}</a>
              </li>
              <li{if $_REQUEST.controller == 'downloads'} class='active'{/if}>
                <a href='/downloads'>{$lang.global.download}</a>
              </li>
              <li{if $_REQUEST.controller == 'searches'} class='active'{/if}>
                <a href='/searches'>{$lang.global.search}</a>
              </li>
            </ul>
            <ul class='nav pull-right'>
              {if $_SESSION.user.id == 0}
                <li{if $_REQUEST.controller == 'users' && isset($_REQUEST.action) && $_REQUEST.action == 'create'} class='active'{/if}>
                  <a href='/users/create'>
                    {$lang.global.register}
                  </a>
                </li>
                <li class='divider-vertical'/>
              {/if}
              {if $_SESSION.user.role == 0}
                <li{if $_REQUEST.controller == 'sessions'} class='active'{/if}>
                  <a href='/sessions/create'>
                    {$lang.global.login}
                  </a>
                </li>
              {else}
                <li class='dropdown'>
                  <a href='#' class='dropdown-toggle' data-toggle='dropdown'>
                    <strong>{$lang.global.welcome} {$_SESSION.user.name}!</strong>
                    <b class='caret'></b>
                  </a>
                  <ul class='dropdown-menu'>
                    {if $_SESSION.user.id > 0}
                      <li>
                        <a href='/users/{$_SESSION.user.id}/update'>
                          {$lang.global.settings}
                        </a>
                      </li>
                    {/if}
                    <li>
                      <a href='/sessions/destroy'>{$lang.global.logout}</a>
                    </li>
                    {if $_SESSION.user.role >= 3}
                      <li class='divider'></li>
                      <li>
                        <a href='/medias' title='{$lang.global.manager.media}'>
                          {$lang.global.manager.media}
                        </a>
                      </li>
                      <li>
                        <a href='/contents' title='{$lang.global.manager.content}'>
                          {$lang.global.manager.content}
                        </a>
                      </li>
                      {if $_SESSION.user.role == 4}
                        <li>
                          <a href='/logs' title='{$lang.global.logs}'>
                            {$lang.global.logs}
                          </a>
                        </li>
                        <li>
                          <a href='/users' title='{$lang.global.manager.user}'>
                            {$lang.global.manager.user}
                          </a>
                        </li>
                        <li>
                          <a href='/mails' title='{$lang.global.mails}'>
                            {$lang.global.mails}
                          </a>
                        </li>
                      {/if}
                    {/if}
                  </ul>
                </li>
              {/if}
            </ul>
          </div>
        </div>
      </nav>

      {* Main container *}
      <div class='container'>
        <div class='row'>
          <div class='span8'>
            {if isset($_FLASH.message)}
              <div id='js-flash_message'>
                <div class='alert alert-{$_FLASH.type}' id='js-flash_{$_FLASH.type}'>
                  <a class='close' href='#'>×</a>
                  <h4 class='alert-heading'>{$_FLASH.headline}</h4>
                  <p>
                    {$_FLASH.message}
                  </p>
                </div>
              </div>
            {/if}
            {if isset($_WEBSITE.update)}
              <div class='alert alert-warning'>
                {$_WEBSITE.update}
              </div>
            {/if}
            <section id='{$_REQUEST.controller}'>
              {$_WEBSITE.content}
            </section>
          </div>
          <div class='span4'>
            <h3>{$lang.global.headlines}</h3>
            <!-- plugin:headlines -->
            <h3>{$lang.global.archive}</h3>
            <!-- plugin:archive -->
            <h3>{$lang.global.tagcloud}</h3>
            <!-- plugin:tagcloud -->
            {if $MOBILE_DEVICE == true}
              <a href='/?mobile=1' ref='nofollow'>{$lang.global.view.mobile}</a>
            {/if}
          </div>
        </div>
      </div>

      {* Add bootstrap support *}
      <script type='text/javascript' src='{$_PATH.js}/core/jquery.bootstrap.dropdown{$_SYSTEM.compress_files_suffix}.js'></script>
      <script type='text/javascript' src='{$_PATH.js}/core/jquery.bootstrap.tooltip{$_SYSTEM.compress_files_suffix}.js'></script>

      {* Own JS and plugins *}
      <script type='text/javascript' src='{$_PATH.js}/core/scripts{$_SYSTEM.compress_files_suffix}.js'></script>
      <!-- plugin:analytics -->
      <!-- plugin:piwik -->
      <!-- plugin:facebook -->
      <!-- plugin:formattimestamp -->
    </body>
  </html>
{/strip}