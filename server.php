<?php
include_once '_common.php';

if (passkeysPluginNeedMigration() === true) {
    return;
}

use Kkigomi\Plugin\Passkeys\MemberFactory;
use Kkigomi\Plugin\Passkeys\Exceptions;
use Kkigomi\Plugin\Passkeys\Webauthn as KkigomiWebauthn;
use function Kkigomi\Plugin\Passkeys\responseJson;

/*
 * Copyright (C) 2022 Lukas Buchs
 * license https://github.com/lbuchs/WebAuthn/blob/master/LICENSE MIT
 *
 * Server test script for WebAuthn library. Saves new registrations in session.
 *
 *            JAVASCRIPT            |          SERVER
 * ------------------------------------------------------------
 *
 *               REGISTRATION
 *
 *      window.fetch  ----------------->     getCreateArgs
 *                                                |
 *   navigator.credentials.create   <-------------'
 *           |
 *           '------------------------->     processCreate
 *                                                |
 *         alert ok or fail      <----------------'
 *
 * ------------------------------------------------------------
 *
 *              VALIDATION
 *
 *      window.fetch ------------------>      getGetArgs
 *                                                |
 *   navigator.credentials.get   <----------------'
 *           |
 *           '------------------------->      processGet
 *                                                |
 *         alert ok or fail      <----------------'
 *
 * ------------------------------------------------------------
 */

try {
    /**
     * @var Member? $currentMember
     */
    $currentMember = MemberFactory::currentMember();

    // \logger(['kg_passkey_authenticated', $_SESSION['kg_passkey_authenticated']]);

    // read get argument and post body
    $fn = filter_input(INPUT_GET, 'fn');

    $requireResidentKey = !!filter_input(INPUT_POST, 'requireResidentKey');
    $userVerification = filter_input(INPUT_POST, 'userVerification', FILTER_SANITIZE_SPECIAL_CHARS);

    $post = trim(file_get_contents('php://input'));
    if ($post) {
        $post = json_decode($post);
    }

    $requestUsername = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));

    // ???????????? ???????????? ?????? ???????????? ?????? ???????????? ??????
    if ($currentMember && $requestUsername) {
        throw new \Error('????????? ??????');
    }

    $rpId = $_SERVER['SERVER_NAME'];
    if (filter_input(INPUT_POST, 'rpId')) {
        $rpId = filter_input(INPUT_POST, 'rpId', FILTER_VALIDATE_DOMAIN);
        if ($rpId === false) {
            throw new Exception('invalid relying party ID');
        }
    }

    $crossPlatformAttachment = null;

    // new Instance of the server library.
    // make sure that $rpId is the domain name.
    $kkigomiWebauthn = new KkigomiWebauthn($currentMember, []);

    $tables = Kkigomi\Plugin\Passkeys\Passkeys::passkeyTables();

    if ($fn === 'getCredentialCreateOptions') {
        // ------------------------------------
        // request for create arguments
        // ------------------------------------
        $response = $kkigomiWebauthn->getCredentialCreateOptions();
        responseJson($response);

    } else if ($fn === 'getCredentialRequestOptions') {

        $response = $kkigomiWebauthn->getCredentialRequestOptions();
        responseJson($response);

    } else if ($fn === 'registrations') {
        $kkigomiWebauthn->registrations($post);

        responseJson([
            'success' => true,
            'msg' => 'registration success'
        ]);

    } else if ($fn === 'authentication') {
        // ------------------------------------
        // proccess get
        // ------------------------------------
        $return = $kkigomiWebauthn->authentication($post);

        $memberInfo = $kkigomiWebauthn->getMember();

        responseJson($return);

    } else if ($fn === 'clearRegistrations') {

        $kkigomiWebauthn->clearRegistrations();

        $return = new stdClass();
        $return->success = true;
        $return->msg = '????????? ???????????? ?????? ??????????????????';

        responseJson($return);

    } else if ($fn === 'removeCredential') {
        $credentialId = $post->credentialId;

        $kkigomiWebauthn->removeCredential($credentialId);

        $return = new stdClass();
        $return->success = true;
        $return->msg = '???????????? ??????????????????';

        responseJson($return);
    } else if ($fn === 'sessionAuth') {
        $type = $post->type ?? KkigomiWebauthn::AUTH_PASSKEY;
        $password = $post->password ?? null;

        $return = $kkigomiWebauthn->sessionAuth($type, $post);

        // ???????????? ?????? ????????? ????????? ?????? ??????
        $provider_name = 'passkeys';
        $social_token = social_nonce_create($provider_name);
        set_session('social_link_token', $social_token);

        $return = [];
        $return['success'] = true;
        $return['memberId'] = $currentMember->getId();
        $return['providerName'] = $provider_name;

        responseJson($return);
    }
} catch (Throwable $ex) {
    $return = [];
    $return['error'] = true;
    $return['success'] = false;
    $return['exception'] = get_class($ex);
    $return['msg'] = $ex->getMessage();

    if($ex instanceof Exceptions\NotFoundUserhandleException) {
        $return['msg'] = '????????? ???????????? ??? ?????? ????????? ??? ????????????';
    }

    http_response_code(400);
    responseJson($return);
}
