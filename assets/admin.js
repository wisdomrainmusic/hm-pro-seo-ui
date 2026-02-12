(function($){
  function strLen(s){
    if (!s) return 0;
    try { return [...s].length; } catch(e){ return s.length; }
  }

  function norm(s){
    return (s || '').toString().trim();
  }

  function getEditorText(){
    // Classic editor: #content + TinyMCE
    try {
      if (window.tinymce && tinymce.get('content') && !tinymce.get('content').isHidden()) {
        return tinymce.get('content').getContent({ format: 'text' }) || '';
      }
    } catch(e){}
    var $ta = $('#content');
    return $ta.length ? ($ta.val() || '') : '';
  }

  function containsH2(htmlOrText){
    // metabox data-long-html is already stripped tags; we fallback to editor html existence via content field
    var raw = (htmlOrText || '').toString();
    // if HTML present, check <h2 ; if plain text, can't detect reliably
    return /<h2\b/i.test(raw);
  }

  function countOccurrences(text, needle){
    text = (text || '').toString().toLowerCase();
    needle = (needle || '').toString().toLowerCase().trim();
    if (!needle) return 0;
    var re = new RegExp(needle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
    var m = text.match(re);
    return m ? m.length : 0;
  }

  function updateAll($wrap){
    var isProduct = $wrap.closest('#post').find('input#post_type').val() === 'product';

    var seoTitle = norm($wrap.find('[data-hmpsui-title]').val());
    var seoDesc  = norm($wrap.find('[data-hmpsui-desc]').val());
    var focus    = norm($wrap.find('[data-hmpsui-focus]').val());
    var shortD   = norm($wrap.find('[data-hmpsui-short]').val());

    var permalink = $wrap.data('permalink') || '';
    var h1 = ( $wrap.data('h1') || '' ).toString();

    // Long description text (we take editor live value)
    var longText = getEditorText();

    // Counters
    $wrap.find('[data-count-title]').text(strLen(seoTitle));
    $wrap.find('[data-count-desc]').text(strLen(seoDesc));

    // Snippet preview
    if ($wrap.find('[data-snippet-title]').length){
      var shownTitle = seoTitle || h1 || '(Başlık girilmedi)';
      var shownDesc  = seoDesc  || '(Açıklama girilmedi)';
      $wrap.find('[data-snippet-title]').text(shownTitle);
      $wrap.find('[data-snippet-desc]').text(shownDesc);
      $wrap.find('[data-snippet-url]').text(permalink);
    }

    // --- E-ticaret odaklı analiz ---
    var issues = [];
    var score = 100;

    var titleLen = strLen(seoTitle);
    var descLen  = strLen(seoDesc);

    if (!seoTitle){ issues.push('SEO Başlık boş.'); score -= 25; }
    if (!seoDesc){ issues.push('SEO Açıklama boş.'); score -= 25; }

    if (seoTitle && (titleLen < 30 || titleLen > 65)){ issues.push('SEO Başlık uzunluğu önerilen aralıkta değil (30–65).'); score -= 10; }
    if (seoDesc && (descLen < 90 || descLen > 170)){ issues.push('SEO Açıklama uzunluğu önerilen aralıkta değil (90–170).'); score -= 10; }

    // Focus keyword checks
    if (!focus){ issues.push('Odak anahtar kelime boş.'); score -= 10; }

    // H1 contains focus?
    if (focus && h1 && h1.toLowerCase().indexOf(focus.toLowerCase()) === -1){
      issues.push('Odak kelime H1 (ürün adı) içinde geçmiyor (öneri).');
      score -= 5;
    }

    // Focus in meta desc?
    if (focus && seoDesc && seoDesc.toLowerCase().indexOf(focus.toLowerCase()) === -1){
      issues.push('Odak kelime SEO Açıklama içinde geçmiyor (öneri).');
      score -= 5;
    }

    // Focus in short desc?
    if (isProduct){
      if (!shortD){ issues.push('Kısa açıklama (ürün özeti) boş.'); score -= 5; }
      if (focus && shortD && shortD.toLowerCase().indexOf(focus.toLowerCase()) === -1){
        issues.push('Odak kelime kısa açıklama içinde geçmiyor (öneri).');
        score -= 3;
      }
    }

    // H2 exists in long description? (classic editor only reliable if HTML headings exist)
    // We check the raw HTML from editor if tinymce exists; otherwise longText is plain.
    var longHtml = '';
    try {
      if (window.tinymce && tinymce.get('content') && !tinymce.get('content').isHidden()) {
        longHtml = tinymce.get('content').getContent() || '';
      } else {
        longHtml = $('#content').val() || '';
      }
    } catch(e){}
    if (isProduct){
      if (!containsH2(longHtml)){
        issues.push('En az 1 adet H2 başlık yok (öneri).');
        score -= 3;
      }
    }

    // Keyword occurrences in long description + short description
    if (focus){
      var occ = countOccurrences((longText + ' ' + shortD), focus);
      if (occ === 0){
        issues.push('Odak kelime açıklamalar içinde hiç geçmiyor.');
        score -= 10;
      } else if (occ >= 12){
        issues.push('Odak kelime çok sık geçiyor (spam riski).');
        score -= 3;
      }
    }

    // Featured image + alt text
    if (isProduct){
      var hasThumb = ($wrap.data('has-thumb') + '') === '1';
      var thumbAlt = norm($wrap.data('thumb-alt'));
      if (!hasThumb){ issues.push('Ürün görseli (featured image) yok.'); score -= 5; }
      else if (!thumbAlt){ issues.push('Ürün görselinde alt text yok (öneri).'); score -= 3; }
    }

    // Category assigned
    if (isProduct){
      var hasCats = ($wrap.data('has-cats') + '') === '1';
      if (!hasCats){ issues.push('Ürün kategorisi atanmadı.'); score -= 5; }
    }

    // Price / stock present (proxy for schema completeness)
    if (isProduct){
      var price = norm($wrap.data('price'));
      var stock = norm($wrap.data('stock'));
      if (!price){ issues.push('Ürün fiyatı yok (schema/ürün verisi zayıf).'); score -= 5; }
      if (!stock){ issues.push('Stok durumu okunamadı (öneri).'); score -= 2; }
    }

    score = Math.max(0, Math.min(100, parseInt(score, 10) || 0));
    $wrap.find('[data-hmpsui-score]').text(score);

    var $list = $wrap.find('[data-hmpsui-issues]');
    if ($list.length){
      $list.empty();
      if (!issues.length){
        $list.append('<li class="ok">✅ Temel kontroller geçti.</li>');
      } else {
        issues.forEach(function(t){
          $list.append('<li>' + $('<div/>').text(t).html() + '</li>');
        });
      }
    }
  }

  function bind(){
    var $wrap = $('[data-hmpsui="1"]');
    if (!$wrap.length) return;

    // initial
    updateAll($wrap);

    // our fields
    $wrap.on('input change', '[data-hmpsui-title],[data-hmpsui-desc],[data-hmpsui-focus],[data-hmpsui-short]', function(){
      updateAll($wrap);
    });

    // product title field
    $(document).on('input change', '#title', function(){
      $wrap.data('h1', $(this).val() || '');
      updateAll($wrap);
    });

    // classic editor changes
    $(document).on('input change', '#content', function(){
      updateAll($wrap);
    });
    try {
      if (window.tinymce){
        $(document).on('tinymce-editor-init', function(){
          updateAll($wrap);
        });
        // polling light (tiny) to catch edits without heavy hooks
        setInterval(function(){ updateAll($wrap); }, 1500);
      }
    } catch(e){}
  }

  $(document).ready(bind);


  // HM Pro: URL/Slug düzenle butonu (WP permalink editörünü aç)
  $(document).on('click', '#hmpsui-edit-slug', function(e){
    e.preventDefault();

    // Başlık alanına scroll (slug edit genelde burada)
    if ($('#titlewrap').length) {
      $('html, body').animate({ scrollTop: $('#titlewrap').offset().top - 80 }, 250);
    }

    // WP'nin slug düzenle butonu (farklı WP sürümlerinde selector değişebiliyor)
    var $editBtn =
      $('#edit-slug-buttons .edit-slug, #edit-slug-box .edit-slug, #edit-slug-buttons button, #edit-slug-box button').first();

    if ($editBtn.length) {
      $editBtn.trigger('click');
      setTimeout(function(){
        if ($('#new-post-slug').length) $('#new-post-slug').focus();
      }, 300);
    } else {
      alert('Kalıcı bağlantı düzenleyicisi bu ekranda bulunamadı. Başlık altındaki "Kalıcı bağlantı" alanını kontrol edin.');
    }
  });

  // HM Pro: Slug değişince URL preview'yi canlı güncelle
  function hmpsui_update_permalink_preview_from_slug(){
    var $slug = $('#new-post-slug');
    var $preview = $('#hmpsui-permalink-preview');
    if (!$slug.length || !$preview.length) return;

    var slugVal = ($slug.val() || '').trim();
    if (!slugVal) return;

    // WP edit ekranındaki permalink yapısını al: #sample-permalink a ya da #sample-permalink
    var $sampleLink = $('#sample-permalink a');
    var sampleHref = $sampleLink.length ? $sampleLink.attr('href') : '';

    // sample-permalink yoksa, mevcut preview URL üzerinden base çıkarıp slug'ı değiştir
    var current = $preview.text().trim();

    // Base URL hesapla (en güvenlisi sample permalink)
    var base;
    if (sampleHref) {
      // sampleHref genelde doğru canonical/permalink yapısını verir
      try {
        var u = new URL(sampleHref, window.location.origin);
        // path'in son segmentini slug yapalım
        var parts = u.pathname.replace(/\/+$/, '').split('/');
        parts[parts.length - 1] = slugVal;
        u.pathname = parts.join('/') + '/';
        base = u.toString();
      } catch(e) {
        base = sampleHref;
      }
    } else {
      // fallback: mevcut URL'nin son slugını değiştir
      base = current.replace(/\/[^\/]*\/?$/, '/' + slugVal + '/');
    }

    $preview.text(base);
  }

  // Slug input açıldığında ve değiştikçe güncelle
  $(document).on('input keyup change', '#new-post-slug', function(){
    hmpsui_update_permalink_preview_from_slug();
  });

  // Sayfa ilk açıldığında (slug zaten varsa) bir kez güncelle
  $(document).ready(function(){
    setTimeout(function(){
      hmpsui_update_permalink_preview_from_slug();
    }, 300);
  });


  // Rank Math sağ panel "SEO: xx/100" kutusunu kesin gizle (CSS kaçarsa bile)
  $(document).ready(function(){
    function hideRMScore(){
      // 1) class üzerinden
      $('.misc-pub-section.rank-math-seo-score, .rank-math-seo-score').hide();

      // 2) içerik üzerinden (SEO: 59 / 100 gibi)
      $('#misc-publishing-actions').find('*').each(function(){
        var t = (this.textContent || '').replace(/\s+/g, ' ').trim();
        if (t.startsWith('SEO:') && t.indexOf('/ 100') !== -1) {
          $(this).closest('.misc-pub-section, div, span, p, li').hide();
        }
      });
    }

    hideRMScore();
    // Rank Math bazen ajax ile yeniden basıyor → birkaç kez daha dene
    setTimeout(hideRMScore, 500);
    setTimeout(hideRMScore, 1500);
    setTimeout(hideRMScore, 3000);
  });
})(jQuery);
