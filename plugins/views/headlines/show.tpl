<section id="headlines">
  <ul>
    {foreach from=$data item=d}
      <li>
        <a href="/blog/{$d.id}/{$d.encoded_title}" title="{$d.teaser}">
          {$d.title}
        </a>
      </li>
    {/foreach}
  </ul>
</section>