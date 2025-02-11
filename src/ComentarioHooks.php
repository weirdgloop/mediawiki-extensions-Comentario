<?php

namespace MediaWiki\Extension\Comentario;

use MediaWiki\Config\Config;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;
use Skin;

class ComentarioHooks implements BeforePageDisplayHook {
	/**
	 * The server that Comentario is accessible on.
	 * @var string
	 */
	private string $server;

	/**
	 * The namespaces that the extension will run on.
	 * @var int[]
	 */
	private $namespaces;

	public function __construct( Config $config ) {
		$this->server = $config->get( 'ComentarioServer' );
		$this->namespaces = $config->get( 'ComentarioNamespaces' ) ?? $config->get( 'ContentNamespaces' );
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return void
	 */
	public function onBeforePageDisplay(
		$out, $skin
	): void {
		if ( empty( $this->server ) ) {
			return;
		}

		$title = $out->getTitle();
		if (
			// Do not run on special pages, or anything other than action=view
			$title->isMainPage() ||
			$title->isSpecialPage() ||
			$out->getActionName() !== 'view' ||
			!in_array( $title->getNamespace(), $this->namespaces )
		) {
			return;
		}

		$out->addModuleStyles( 'ext.comentario.styles' );
		if ( !$out->getContext()->getAuthority()->isRegistered() ) {
			$out->addModuleStyles( 'ext.comentario.anon.styles' );
		}

		$pageId = $title->getId();

		$out->addScriptFile( $this->server . '/comentario.js' );
		$out->addHTML(
			'<div class="comentario-container"><h3><span class="mw-comentario-comments-icon"></span> Comments</h3>'
			. "<comentario-comments id='comentario-comments' page-id='/$pageId' theme='light' auto-init='false' auto-non-interactive-sso='false'></comentario-comments></div>"
		);
		$out->addModules( 'ext.comentario' );
	}
}
