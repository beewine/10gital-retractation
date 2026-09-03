/**
 * 10gital Rétractation — interactions du formulaire public.
 *
 * Le formulaire fonctionne entièrement sans JavaScript ; ce fichier n'ajoute
 * que des raccourcis de confort. Aucune donnée n'est envoyée nulle part.
 */
( function () {
	'use strict';

	function quantities( form ) {
		return Array.prototype.slice.call( form.querySelectorAll( '[data-ret10g-qty]' ) );
	}

	function setAll( form, useMax ) {
		quantities( form ).forEach( function ( input ) {
			input.value = useMax ? input.getAttribute( 'data-ret10g-max' ) || '0' : '0';
		} );
	}

	function hasSelection( form ) {
		return quantities( form ).some( function ( input ) {
			return parseInt( input.value, 10 ) > 0;
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-ret10g-select-all], [data-ret10g-select-none]' );

		if ( ! trigger ) {
			return;
		}

		var form = trigger.closest( '.ret10g' ).querySelector( 'form.ret10g__form--declare' );

		if ( ! form ) {
			return;
		}

		event.preventDefault();
		setAll( form, trigger.hasAttribute( 'data-ret10g-select-all' ) );
	} );

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;

		if ( ! form.classList || ! form.classList.contains( 'ret10g__form--declare' ) ) {
			return;
		}

		if ( hasSelection( form ) ) {
			return;
		}

		event.preventDefault();

		var alertBox = form.parentNode.querySelector( '[data-ret10g-client-error]' );

		if ( ! alertBox ) {
			alertBox = document.createElement( 'div' );
			alertBox.className = 'ret10g__alert ret10g__alert--error';
			alertBox.setAttribute( 'role', 'alert' );
			alertBox.setAttribute( 'data-ret10g-client-error', '' );
			form.parentNode.insertBefore( alertBox, form );
		}

		alertBox.textContent = form.getAttribute( 'data-ret10g-empty-message' ) ||
			'Merci de sélectionner au moins un article.';
		alertBox.scrollIntoView( { behavior: 'smooth', block: 'center' } );
	} );
}() );
