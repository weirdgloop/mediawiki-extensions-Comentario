<?php

namespace MediaWiki\Extension\Comentario;

use MediaWiki\Config\Config;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\UserFactory;
use Wikimedia\ParamValidator\ParamValidator;

class RestApiSSO extends SimpleHandler {
	/**
	 * @var Config
	 */
	private Config $config;

	/**
	 * @var UserFactory
	 */
	private UserFactory $userFactory;

	public function __construct( Config $config, UserFactory $userFactory ) {
		$this->config = $config;
		$this->userFactory = $userFactory;
	}

	/**
	 * @throws HttpException
	 */
	public function run() {
		$params = $this->getValidatedParams();
		$token = $params['token'];
		$hmac = $params['hmac'];

		// First, validate that the HMAC matches the HMAC-SHA256 of the passed token
		$generatedHmac = hash_hmac( 'sha256', hex2bin( $token ), hex2bin( $this->config->get( 'ComentarioSSOSecret' ) ) );
		if ( $hmac !== $generatedHmac ) {
			throw new HttpException( 'Invalid HMAC', 400 );
		}

		// TODO: if user is blocked, deny the SSO request maybe?

		$auth = $this->getAuthority();
		if ( !$auth->isRegistered() ) {
			throw new HttpException( 'Not logged in', 401 );
		}

		$user = $this->userFactory->newFromAuthority( $auth );
		$payload = [
			"token" => $token,
			"name" => $user->getName(),
			"link" => $user->getUserPage()->getFullURL( '', false, PROTO_HTTPS ),
			// Comentario does not have validation on the email, so we can pass it a unique string instead..
			// This is quite hacky, but is kind of the only way to deal with this for now.
			// See: https://gitlab.com/comentario/comentario/-/issues/100
			"email" => $this->config->get( 'DBname' ) . '|' . $user->getName()
		];

		$payloadJson = json_encode( $payload );
		$payloadHmac = hash_hmac( 'sha256', $payloadJson, hex2bin( $this->config->get( 'ComentarioSSOSecret' ) ) );
		$payloadHex = bin2hex( $payloadJson );

		// Redirect to the callback URL
		return $this->getResponseFactory()->createTemporaryRedirect(
			$this->config->get( 'ComentarioServer' ) . "/api/oauth/sso/callback?payload=$payloadHex&hmac=$payloadHmac"
		);
	}

	public function getParamSettings() {
		return [
			'token' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'hmac' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			]
		];
	}
}
