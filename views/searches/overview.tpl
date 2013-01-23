{strip}
  <div class='page-header'>
    <h1>{$lang.global.search}</h1>
  </div>
  {if $tables.blogs.entries > 0 || $tables.contents.entries > 0 || $tables.downloads.entries > 0 || $tables.gallery_albums.entries > 0}
    <div class='tabbable'>
      <ul class='nav nav-tabs'>
        {foreach $tables as $table}
          <li{if $table@first} class='active'{/if}>
            <a href='#search-{$table.title|lower}' data-toggle='tab'>{$table.title} ({$table.entries})</a>
          </li>
        {/foreach}
      </ul>
    </div>
    <div class='tab-content'>
      {foreach $tables as $table}
        <div class='tab-pane{if $table@first} active{/if}' id='search-{$table.title|lower}'>
          {if $table.entries == 0}
            <div class='alert alert-warning'>
              <h4>{$lang.error.missing.entries}</h4>
            </div>
          {else}
            <ol>
              {foreach $table as $data}
                {if $data.id > 0}
                  <li>
                    <a href='{$data.url_clean}/highlight/{$string}'>
                      {$data.title}
                    </a>,
                    &nbsp;
                    <time datetime='{$data.date.w3c}' class='js-timeago'>
                      {$data.date.raw|date_format:$lang.global.time.format.datetime}
                    </time>
                  </li>
                {/if}
              {/foreach}
            </ol>
          {/if}
        </div>
      {/foreach}
    </div>
  {else}
    <div class='alert alert-warning'>
      {$lang.searches.info.fail|replace:'%s':$string}
      <br />
      <a href='/{$_REQUEST.controller}'>{$lang.searches.info.research}</a>
    </div>
  {/if}
  <script type='text/javascript' src='{$_PATH.core}/assets/javascripts/core/jquery.bootstrap.tabs{$_SYSTEM.compress_files_suffix}.js'></script>
{/strip}