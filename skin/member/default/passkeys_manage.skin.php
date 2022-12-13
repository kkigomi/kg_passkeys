<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

$kgPasskeyMember = Kkigomi\Plugin\Passkeys\MemberFactory::currentMember();
$kgPasskeyCredentials = $kgPasskeyMember->getCredential();

/**
 * 아래 CSS Class는 등록, 삭제 등 기능을 동작시키는데 필요합니다.
 * 이외의 class는 자유롭게 변경하세요
 *
 * - .passkeys-action--add-key
 *      새로운 패스키 등록 버튼
 * - .passkeys-register-form
 *      패스키 등록폼
 * - .passkeys-register-keyname
 *      새로운 패스키 등록 시 패스키 항목의 이름 입력 필드
 * - .passkeys-action--remove-key
 *      하나의 등록된 패스키 삭제
 * - .passkeys-action--remove-all
 *      등록된 패스키 전체 삭제 및 회원의 패스키 설정 초기화
 */
?>
<section class="passkeys-manage">
    <fieldset class="passkeys-controls">
        <legend>패스키 등록</legend>
        <form class="passkeys-controls__form passkeys-register-form">
            <input type="text" class="passkeys-input passkeys-register-keyname" required placeholder="키 이름" />
            <button type="submit" class="passkeys-button passkeys-button--add passkeys-action--add-key"> 패스키 등록</button>
        </form>

        <button type="button" class="passkeys-button passkeys-button--remove passkeys-action--remove-all">
            <i class="fa fa-trash-o" aria-hidden="true"></i> 모두 제거
        </button>
    </fieldset>

    <?php if ($kgPasskeyCredentials): ?>
    <ul class="passkeys-manage__list">
        <?php foreach ($kgPasskeyCredentials as $credential): ?>
        <li class="passkeys-item">
            <p class="paskeys-item__info">
                <strong class="paskeys-item__name">
                    <?= $credential['authenticator_name'] ?? '(이름 없음)' ?>
                </strong>
                — <span class="paskeys-item__created-at">
                    <?= date('Y-m-d', strtotime($credential['created_at'])) ?>
                </span> 등록 됨
                <br>
                <em class="paskeys-item__lastused-at">(마지막 사용 : <?= $credential['lastused_at'] ?>)</em>
            </p>

            <button type="button" data-credential-id="<?= $credential['credential_id'] ?>"
                class="passkeys-button passkeys-button--remove passkeys-action--remove-key">
                <i class="fa fa-trash-o" aria-hidden="true"></i> 제거
            </button>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>

<style>
    .passkeys-manage {
        margin: 30px auto;
        max-width: 600px;
        font-size: 0.875rem;
    }

    .passkeys-controls {
        display: flex;
        padding: 0.8em 1em;
    }

    .passkeys-controls__form {
        flex-grow: 1;
    }

    .passkeys-controls+.passkeys-manage__list {
        margin-top: 0.8em;
        border-top: 1px solid #e2e8f0;
    }

    .passkeys-item {
        display: flex;
        align-items: center;
        margin: 1.2em 0;
        padding: 0.8em 1em;
        border-radius: 12px;
        background-color: #f1f5f9;
    }

    .passkeys-item::before {
        content: '';
        display: block;
        margin: 0.5em;
        margin-right: 1em;
        width: 2em;
        height: 2em;
        background-image: url('<?= KG_PASSKEYS_URL ?>/assets/FIDO-Passkey_Icon-Black.png');
        background-size: contain;
        background-position: center;
        background-repeat: no-repeat;
    }

    .passkeys-item:hover {
        background-color: #e2e8f0;
    }

    .paskeys-item__info {
        flex-grow: 1;
    }

    .paskeys-item__lastused-at {
        font-size: 0.9em;
    }

    .passkeys-input {
        border: 1px solid #d0d3db;
        border-radius: 3px;
        padding: 5px;
        color: #000;
        vertical-align: middle;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
        background: #fff;
    }

    .passkeys-button {
        padding: 5px 10px;
        border-radius: 3px;
        border: 0 none;
        background: transparent;
    }

    .passkeys-button--add {
        border: 1px solid #f1f5f9;
        border-radius: 3px;
        padding: 5px 10px;
        color: #f1f5f9;
        vertical-align: middle;
        background: #64748b;
    }

    .passkeys-button--add:hover,
    .passkeys-button--add:focus {
        background-color: #334155;
    }

    .passkeys-button--remove {

        color: #e11d48;
    }

    .passkeys-button--remove:hover,
    .passkeys-button--remove:focus {
        color: #f8fafc;
        background-color: #e11d48;
    }

</style>
