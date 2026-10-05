(function () {
	'use strict';

	// Переключение темы (раннее значение data-theme уже выставлено в <head>)
	var html = document.documentElement;
	var themeBtn = document.getElementById( 'themeToggle' );
	if ( themeBtn ) {
		themeBtn.addEventListener( 'click', function () {
			var next = html.dataset.theme === 'dark' ? 'light' : 'dark';
			html.dataset.theme = next;
			try {
				localStorage.setItem( 'theme', next );
			} catch ( e ) {}
		} );
	}

	// Мобильное меню
	var burgerBtn = document.getElementById( 'burgerBtn' );
	var mobileNav = document.getElementById( 'mobileNav' );
	if ( burgerBtn && mobileNav ) {
		burgerBtn.addEventListener( 'click', function () {
			mobileNav.classList.toggle( 'open' );
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! burgerBtn.contains( e.target ) && ! mobileNav.contains( e.target ) ) {
				mobileNav.classList.remove( 'open' );
			}
		} );
	}

	// Кнопка "наверх"
	var scrollTopBtn = document.getElementById( 'scrollTop' );
	if ( scrollTopBtn ) {
		window.addEventListener( 'scroll', function () {
			scrollTopBtn.classList.toggle( 'visible', window.scrollY > 400 );
		} );
		scrollTopBtn.addEventListener( 'click', function () {
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		} );
	}

	// Появление блоков при скролле
	if ( 'IntersectionObserver' in window ) {
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'visible' );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ threshold: 0.12, rootMargin: '-15% 0px -15% 0px' }
		);
		document.querySelectorAll( '.reveal' ).forEach( function ( el ) {
			observer.observe( el );
		} );
	} else {
		document.querySelectorAll( '.reveal' ).forEach( function ( el ) {
			el.classList.add( 'visible' );
		} );
	}

	// Фильтр отчётов по году
	var yearBtns = document.querySelectorAll( '.year-btn' );
	var reportItems = document.querySelectorAll( '.report-item' );
	if ( yearBtns.length && reportItems.length ) {
		var filterByYear = function ( year ) {
			reportItems.forEach( function ( item ) {
				item.style.display = ( item.dataset.year === year ) ? '' : 'none';
			} );
		};
		yearBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				yearBtns.forEach( function ( b ) {
					b.classList.remove( 'active' );
				} );
				btn.classList.add( 'active' );
				filterByYear( btn.dataset.year );
			} );
		} );
		filterByYear( yearBtns[ 0 ].dataset.year );
	}

	initGalleryCarousels();

	/**
	 * Turns any Gutenberg gallery block or classic [gallery] shortcode
	 * output inside the article body into a horizontal scroll-snap carousel.
	 */
	function initGalleryCarousels() {
		var galleries = document.querySelectorAll( '.article-body .wp-block-gallery, .article-body .gallery' );

		galleries.forEach( function ( gallery ) {
			if ( gallery.closest( '.db-carousel' ) ) return;

			var items = gallery.querySelectorAll( ':scope > figure, :scope > .gallery-item, :scope > li' );
			if ( items.length < 2 ) return;

			var fullSrcList = [];
			items.forEach( function ( item, i ) {
				item.removeAttribute( 'style' );

				var img = item.querySelector( 'img' );
				if ( ! img ) return;
				fullSrcList.push( getFullSrc( img ) );

				var link = item.querySelector( 'a' );
				link && link.addEventListener( 'click', function ( e ) { e.preventDefault(); } );

				img.style.cursor = 'zoom-in';
				img.addEventListener( 'click', function () {
					openLightbox( fullSrcList, i );
				} );
			} );

			var wrap = document.createElement( 'div' );
			wrap.className = 'db-carousel';
			gallery.parentNode.insertBefore( wrap, gallery );
			wrap.appendChild( gallery );
			gallery.classList.add( 'db-carousel-track' );

			var prev = document.createElement( 'button' );
			prev.type = 'button';
			prev.className = 'db-carousel-btn prev';
			prev.setAttribute( 'aria-label', 'Предыдущее фото' );
			prev.innerHTML = '&#8249;';

			var next = document.createElement( 'button' );
			next.type = 'button';
			next.className = 'db-carousel-btn next';
			next.setAttribute( 'aria-label', 'Следующее фото' );
			next.innerHTML = '&#8250;';

			wrap.appendChild( prev );
			wrap.appendChild( next );

			function scrollByItem( dir ) {
				var item = gallery.querySelector( ':scope > *' );
				var step = item ? item.getBoundingClientRect().width + 12 : gallery.clientWidth * 0.8;
				gallery.scrollBy( { left: dir * step, behavior: 'smooth' } );
			}

			prev.addEventListener( 'click', function () { scrollByItem( -1 ); } );
			next.addEventListener( 'click', function () { scrollByItem( 1 ); } );
		} );
	}

	/**
	 * Picks the highest-resolution URL available for an <img>. EWWW's lazy
	 * loader swaps src/srcset for a placeholder and keeps the real URLs in
	 * data-src/data-srcset until the image scrolls into view, so those must
	 * be checked first or the lightbox opens on a blank placeholder.
	 */
	function getFullSrc( img ) {
		var srcset = img.getAttribute( 'data-srcset' ) || img.getAttribute( 'srcset' );

		if ( srcset ) {
			var best = null;
			srcset.split( ',' ).forEach( function ( entry ) {
				var parts = entry.trim().split( /\s+/ );
				var width = parseInt( parts[ 1 ], 10 ) || 0;
				if ( ! best || width > best.width ) best = { url: parts[ 0 ], width: width };
			} );
			if ( best ) return best.url;
		}

		return img.getAttribute( 'data-src' ) || img.currentSrc || img.src;
	}

	var lightbox = null;

	/**
	 * Lazily builds the fullscreen viewer and appends it to <body>. Reused
	 * across every gallery on the page.
	 */
	function getLightbox() {
		if ( lightbox ) return lightbox;

		var el = document.createElement( 'div' );
		el.className = 'db-lightbox';
		el.setAttribute( 'aria-hidden', 'true' );
		el.innerHTML =
			'<button type="button" class="db-lightbox-close" aria-label="Закрыть">&times;</button>' +
			'<button type="button" class="db-lightbox-btn prev" aria-label="Предыдущее фото">&#8249;</button>' +
			'<button type="button" class="db-lightbox-btn next" aria-label="Следующее фото">&#8250;</button>' +
			'<div class="db-lightbox-stage"><img class="db-lightbox-img" src="" alt=""></div>' +
			'<div class="db-lightbox-counter"></div>';
		document.body.appendChild( el );

		lightbox = {
			el: el,
			img: el.querySelector( '.db-lightbox-img' ),
			counter: el.querySelector( '.db-lightbox-counter' ),
			prev: el.querySelector( '.db-lightbox-btn.prev' ),
			next: el.querySelector( '.db-lightbox-btn.next' ),
			images: [],
			index: 0
		};

		function render() {
			lightbox.img.src = lightbox.images[ lightbox.index ];
			lightbox.counter.textContent = ( lightbox.index + 1 ) + ' / ' + lightbox.images.length;
			var multi = lightbox.images.length > 1;
			lightbox.prev.style.display = multi ? '' : 'none';
			lightbox.next.style.display = multi ? '' : 'none';
		}

		function show( dir ) {
			var len = lightbox.images.length;
			lightbox.index = ( lightbox.index + dir + len ) % len;
			render();
		}

		function close() {
			el.classList.remove( 'is-open' );
			el.setAttribute( 'aria-hidden', 'true' );
			document.body.classList.remove( 'lightbox-open' );
			lightbox.img.src = '';
		}

		lightbox.open = function ( images, index ) {
			lightbox.images = images;
			lightbox.index = index;
			render();
			el.classList.add( 'is-open' );
			el.setAttribute( 'aria-hidden', 'false' );
			document.body.classList.add( 'lightbox-open' );
		};

		el.querySelector( '.db-lightbox-close' ).addEventListener( 'click', close );
		lightbox.prev.addEventListener( 'click', function () { show( -1 ); } );
		lightbox.next.addEventListener( 'click', function () { show( 1 ); } );
		el.addEventListener( 'click', function ( e ) {
			if ( e.target === el || e.target.classList.contains( 'db-lightbox-stage' ) ) close();
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! el.classList.contains( 'is-open' ) ) return;
			if ( e.key === 'Escape' ) close();
			if ( e.key === 'ArrowLeft' ) show( -1 );
			if ( e.key === 'ArrowRight' ) show( 1 );
		} );

		var touchStartX = null;
		el.addEventListener( 'touchstart', function ( e ) { touchStartX = e.changedTouches[ 0 ].clientX; }, { passive: true } );
		el.addEventListener( 'touchend', function ( e ) {
			if ( touchStartX === null ) return;
			var dx = e.changedTouches[ 0 ].clientX - touchStartX;
			if ( Math.abs( dx ) > 40 ) show( dx > 0 ? -1 : 1 );
			touchStartX = null;
		}, { passive: true } );

		return lightbox;
	}

	function openLightbox( images, index ) {
		getLightbox().open( images, index );
	}
} )();
