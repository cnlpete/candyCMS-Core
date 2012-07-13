{strip}
  {if $_SESSION.user.role == 4}
    <p class='center'>
      <a href='/{$_REQUEST.controller}/create'>
        <img src='{$_PATH.images}/candy.global/spacer.png'
             class='icon-create'
             alt='{$lang.global.create.entry}'
             width='16' height='16' />
        {$lang.users.title.create}
      </a>
    </p>
  {/if}
  <div class='page-header'>
    <h1>{$lang.users.title.overview}</h1>
  </div>
  <table class='table'>
    <thead>
      <tr>
        <th class='column-id headerSortDown'>{$lang.global.id}</th>
        <th class='column-icon'></th>
        <th class='column-name'>{$lang.global.name}</th>
        <th class='column-registered_since center'>{$lang.users.label.registered_since}</th>
        <th class='column-last_login center'>{$lang.users.label.last_login}</th>
        <th class='column-newsletter center'>{$lang.global.newsletter}</th>
        {if $_SESSION.user.role == 4}
          <th class='column-actions'></th>
        {/if}
      </tr>
    </thead>
    {foreach $user as $u}
      <tr>
        <td>{$u.id}</td>
        <td>
          <a href='{$u.avatar_popup}' class='thumbnail js-fancybox'
             title='{$u.full_name}'>
            <img src='{$u.avatar_32}' width='25' height='25' alt='' />
          </a>
        </td>
        <td>
          <a href='{$u.url}'>{$u.full_name}</a>
          <br />
          {if $u.role == 1}
            ({$lang.global.user.roles.1})
          {elseif $u.role == 2}
            ({$lang.global.user.roles.2})
          {elseif $u.role == 3}
            ({$lang.global.user.roles.3})
          {elseif $u.role == 4}
            ({$lang.global.user.roles.4})
          {/if}
        </td>
        <td class='center'>
          {if $u.verification_code}
            <span style='text-decoration:line-through'>
              <time datetime='{$u.date.w3c}' class='js-timeago'>
                {$u.date.raw|date_format:$lang.global.time.format.date}
              </time>
            </span>
          {else}
            <time datetime='{$u.date.w3c}' class='js-timeago'>
              {$u.date.raw|date_format:$lang.global.time.format.date}
            </time>
          {/if}
        </td>
        <td class='center'>
          {if $u.last_login}
            <time datetime='{$u.last_login.w3c}' class='js-timeago'>
              {$u.last_login.raw|date_format:$lang.global.time.format.date}
            </time>
          {else}
            -
          {/if}
        </td>
        <td class='center'>
          <img src='{$_PATH.images}/candy.global/spacer.png'
               class='icon-{if $u.receive_newsletter == 1}success{else}close{/if}'
               alt='{if $u.receive_newsletter == 1}✔{else}✖{/if}' width='16'
               title='{if $u.receive_newsletter == 1}✔{else}✖{/if}' width='16'
               height='16' title='{if $u.receive_newsletter == 1}✔{else}✖{/if}' />
        </td>
        {if $_SESSION.user.role == 4}
          <td class='center'>
            <a href='{$u.url_update}'>
              <img src='{$_PATH.images}/candy.global/spacer.png'
                   class='icon-update js-tooltip'
                   alt='{$lang.global.update.update}'
                   title='{$lang.global.update.update}'
                   width='16' height='16' />
            </a>
            &nbsp;
            <a href='#' onclick="confirmDestroy('{$u.url_destroy}')">
              <img src='{$_PATH.images}/candy.global/spacer.png'
                   class='icon-destroy js-tooltip'
                   alt='{$lang.global.destroy.destroy}'
                   title='{$lang.global.destroy.destroy}'
                   width='16' height='16' />
            </a>
          </td>
        {/if}
      </tr>
    {/foreach}
  </table>
  {$_pages_}
  <script type='text/javascript' src='{$_PATH.js}/core/jquery.fancybox{$_SYSTEM.compress_files_suffix}.js'></script>
  <script type='text/javascript' src='{$_PATH.js}/core/jquery.tablesorter{$_SYSTEM.compress_files_suffix}.js'></script>
  <script src='{$_PATH.js}/core/jquery.infiniteScroll{$_SYSTEM.compress_files_suffix}.js' type='text/javascript'></script>
  <script type='text/javascript'>
    {if $_AUTOLOAD_.enabled}
      $(document).ready(function(){
        enableInfiniteScroll('table', 'table tbody tr', {$_AUTOLOAD_.times});
      });
    {/if}

    $('.js-fancybox').fancybox({ nextEffect : 'fade', prevEffect : 'fade' });
    $('table').tablesorter();
  </script>
{/strip}