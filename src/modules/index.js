$(function () {
	'use strict';

	const cc = document.getElementById( 'comentario-comments' );
	cc.main().then(() => {
		// If the user is logged in to MediaWiki, start the non-interactive SSO flow for Comentario
		if ( mw.config.get('wgUserId') ) {
			cc.nonInteractiveSsoLogin();
		}
	});
});
