{if $USER_RIGHT >= 3}
  <p class="center">
    <a href='/gallery/{$_request_id_}/createfile'>
      <img src='%PATH_IMAGES%/spacer.png' class="icon-create" alt='' width="16" height="16" />
      {$lang.gallery.files.title.create}
    </a>
  </p>
{/if}
<section id="gallery">
  <h1>
    {$gallery_name} ({$file_no} {$lang.global.files})
    <a href='/rss/gallery/{$_request_id_}'>
      <img src='%PATH_IMAGES%/spacer.png' class="icon-rss" alt='{$lang.global.rss}' width="16" height="16" />
    </a>
    {if $USER_RIGHT >= 3}
      <a href='/gallery/{$_request_id_}/update'>
        <img src='%PATH_IMAGES%/spacer.png' class="icon-update" alt='{$lang.global.update.update}'
             width="16" height="16" title='{$lang.global.update.update}' />
      </a>
    {/if}
  </h1>
  {if $gallery_content}
    <h3>{$gallery_content}</h3>
  {/if}
  {if !$files}
    <div class='error' id='js-error'>
      <p>{$lang.error.gallery.no_files}</p>
    </div>
  {else}
    <ul class="js-caption">
      {foreach $files as $f}
        <li>
          <a href='{$f.url_popup}' class="js-fancybox" rel="images" title='{$f.content}'>
            <img src='{$f.url_thumb}'
                 alt='{$f.content}'
                 title='{$f.content}'
                 class="js-image" />
          </a>
          {if $USER_RIGHT >= 3}
            <div>
              <a href="/gallery/{$f.id}/updatefile">
                <img src="%PATH_IMAGES%/spacer.png" class="icon-update"
                     alt="{$lang.global.update.update}" title="{$lang.global.update.update}" width="16" height="16" />
              </a>
              <a href="/gallery/{$f.id}/destroyfile">
                <img src="%PATH_IMAGES%/spacer.png" class="icon-destroy"
                     alt="{$lang.global.destroy.destroy}" title="{$lang.global.destroy.destroy}" width="16" height="16" />
              </a>
            </div>
          {/if}
        </li>
      {/foreach}
    </ul>
  {/if}
</section>
<script src='%PATH_PUBLIC%/js/core/jquery.fancybox{$_compress_files_suffix_}.js' type='text/javascript'></script>
<script src='%PATH_PUBLIC%/js/core/jquery.lazyload{$_compress_files_suffix_}.js' type='text/javascript'></script>
<script type="text/javascript">
  $(document).ready(function(){
    $(".js-fancybox").fancybox();
    $(".js-image").lazyload({ threshold : 200, effect : "fadeIn" });
  });
</script>