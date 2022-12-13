# 그누보드 패스키(Passkeys) 로그인 지원 플러그인
그누보드에 FIDO2, WebAuthn 기반의 로그인 기능을 추가하는 플러그인입니다.

Apple, Google, Microsoft 등이 주도하고 있는 Passkeys 기술을 적용하여 그누보드에 비밀번호 없는 로그인 기능을 지원합니다.


## 지원하는 기능
- Apple Safari, Google Chrome, MS Eged 등의 모던 브라우저에서 패스키 로그인
  - 모바일 장치의 Authenticator 및 외부 보안키 지원
- 기존 회원 계정에 패스키 등록 및 관리
- outlogin 로그인폼에서 패스키 자동완성 및 선택하여 패스키로 로그인
- 회원정보수정 페이지에 비밀번호 대신 패스키로 인증하기

### 계획 중인 기능 추가/개선
- [ ] 패스키를 등록한 계정에서 비밀번호를 제한하는 선택사항 제공
  - 비밀번호 로그인을 차단하고 패스키로만 로그인
- [ ] 회원가입 시 비밀번호 대신 패스키로 등록
- [ ] API 개선 및 가이드 제공

## 설치 방법
- 설치 경로: `/plugin/kg_passkeys`
- extend 파일 설치
  - 배포 파일 내 `_extend/kg_passkeys.extend.php` 파일을 그누보드를 설치한 최상위 폴더의 `/extend` 폴더에 복사
  - extend 폴더에 파일이 없으면 이 플러그인은 동작할 수 없습니다
- 최초 설치 및 패치 후 DB 테이블 생성 및 업데이트가 필요할 수 있습니다
  - 테이블 생성 및 업데이트가 완료되지 않으면 **이 플러그인이 지원하는 모든 동작이 중지**됩니다
  - 플러그인 최초 설치 및 패치 파일 적용 후 **최고관리자로 사이트에 접근하면 자동으로 설치 및 업데이트가 적용**됩니다

## License (LGPL-2.1-or-later)

Copyright (C) 2022 Kkigomi

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301
USA
