<?php if(!defined('ABSPATH'))exit; ?>
<aside class="feng-reading-tools" data-reading-tools hidden aria-label="阅读工具">
 <details class="feng-reading-toc"><summary><?php echo feng_icon('words'); ?><span>目录</span><span data-reading-percent>0%</span></summary><nav aria-label="文章目录"><p>本文目录</p><ol></ol></nav></details>
 <button type="button" data-reading-focus aria-pressed="false" aria-label="开启专注阅读" title="专注阅读"><?php echo feng_icon('reading'); ?></button>
 <button type="button" data-reading-print aria-label="打印文章" title="打印文章"><?php echo feng_icon('print'); ?></button>
 <a href="#xf-article-text" aria-label="回到正文开头" title="回到正文开头"><i class="fa-solid fa-square-up" aria-hidden="true"></i></a>
 <progress max="100" value="0" aria-label="文章阅读进度"></progress>
 <span class="screen-reader-text" role="status" data-reading-status></span>
</aside>
