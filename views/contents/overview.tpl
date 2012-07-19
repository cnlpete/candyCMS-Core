{strip}
  {if $_SESSION.user.role >= 3}
    <p class='center'>
      <a href='/{$_REQUEST.controller}/create'>
        <img src='{$_PATH.images}/candy.global/spacer.png'
            class='icon-plus'
            alt='{$lang.global.create.entry}'
            width='16' height='16' />
        {$lang.global.create.entry}
      </a>
    </p>
  {/if}
  <div class='page-header'>
    <h1>{$lang.global.contents}</h1>
  </div>
  <table class='table'>
    <thead>
      <tr>
        <th class='column-id headerSortDown'>#</th>
        <th class='column-title'>{$lang.global.title}</th>
        <th class='column-date'>{$lang.global.date.date}</th>
        <th>{$lang.global.author}</th>
        {if $_SESSION.user.role >= 3}
          <th class='column-published center'>{$lang.global.published}</th>
          <th class='column-actions'></th>
        {/if}
      </tr>
    </thead>
    {foreach $contents as $c}
      <tr>
        <td>{$c.id}</td>
        <td>
          <a href='{$c.url}'>
            {$c.title}
          </a>
        </td>
        <td>
          <time datetime='{$c.date.w3c}' class='js-timeago'>
            {$c.date.raw|date_format:$lang.global.time.format.datetime}
          </time>
        </td>
        <td>
          <a href='{$c.author.url}'>
            {$c.author.full_name}
          </a>
        </td>
        {if $_SESSION.user.role >= 3}
          <td class='center'>
            <img src='{$_PATH.images}/candy.global/spacer.png'
                 class='icon-{if $c.published == true}ok{else}remove{/if}'
                 alt='{if $c.published == true}✔{else}✖{/if}' height='16'
                 title='{if $c.published == true}✔{else}✖{/if}' width='16' />
          </td>
          <td>
            <a href='{$c.url_update}'>
              <img src='{$_PATH.images}/candy.global/spacer.png'
                   class='icon-pencil js-tooltip'
                   alt='{$lang.global.update.update}'
                   title='{$lang.global.update.update}'
                   width='16' height='16' />
            </a>
            &nbsp;
            <a href='#' onclick="confirmDestroy('{$c.url_destroy}')">
              <img src='{$_PATH.images}/candy.global/spacer.png'
                   class='icon-trash js-tooltip'
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
  <script type='text/javascript' src='{$_PATH.js}/core/jquery.tablesorter{$_SYSTEM.compress_files_suffix}.js'></script>
  <script src='{$_PATH.js}/core/jquery.infiniteScroll{$_SYSTEM.compress_files_suffix}.js' type='text/javascript'></script>
  <script type='text/javascript'>
    {if $_AUTOLOAD_.enabled}
      $(document).ready(function(){
        enableInfiniteScroll('table', 'table tbody tr', {$_AUTOLOAD_.times});
      });
    {/if}
    $('table').tablesorter();
  </script>
{/strip}