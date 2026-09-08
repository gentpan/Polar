<?php if(!defined('ABSPATH'))exit; ?>
<aside class="feng-reading-tools" id="feng-article-toc" data-reading-tools hidden aria-label="阅读工具">
 <div class="feng-reading-tools__heading"><span>文章目录</span><button type="button" data-reading-close aria-label="关闭文章目录"><?php echo feng_icon('close'); ?></button></div>
 <details class="feng-reading-toc" open><summary>目录</summary><nav aria-label="文章目录"><p>本文目录</p><ol></ol></nav></details>
 <button type="button" data-reading-focus aria-pressed="false" aria-label="开启专注阅读" title="专注阅读"><?php echo feng_icon('reading'); ?></button>
 <button type="button" data-reading-print aria-label="打印文章" title="打印文章"><?php echo feng_icon('print'); ?></button>
 <span class="screen-reader-text" role="status" data-reading-status></span>
</aside>
