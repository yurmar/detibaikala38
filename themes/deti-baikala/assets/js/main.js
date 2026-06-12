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
			{ threshold: 0.12, rootMargin: '-35% 0px -35% 0px' }
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
} )();
