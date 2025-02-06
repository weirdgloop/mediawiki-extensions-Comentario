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
			// If there is no script path configured, then simply show nothing.
			return;
		}

		$title = $out->getTitle();
		if (
			$title->isMainPage() ||
			$title->isSpecialPage() ||
			$out->getActionName() !== 'view' ||
			!in_array( $title->getNamespace(), $this->namespaces )
		) {
			// Do not run on special pages, or anything other than action=view
			return;
		}

		$out->addModuleStyles( 'ext.comentario.styles' );
		$out->addScriptFile( $this->server . '/comentario.js' );
		$out->addHTML( '<div class="comentario-container"><h3><span class="mw-comentario-comments-icon"></span> Comments</h3><comentario-comments id="comentario-comments" auto-non-interactive-sso="true" theme="light"></comentario-comments></div>' );
	}
}
