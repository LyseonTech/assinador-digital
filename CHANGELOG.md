# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and follows the requirements of the [Nextcloud Appstore Metadata specification](https://nextcloudappstore.readthedocs.io/en/latest/developer.html#changelog).

Types of changes:
- *Added* for new features.
- *Changed* for changes in existing functionality.
- *Deprecated* for soon-to-be removed features.
- *Removed* for now removed features.
- *Fixed* for any bug fixes.
- *Security* in case of vulnerabilities. 

<!-- changelog-linker -->

## 2.4.2

# Added
- List of documents
- User profile
- Filter files
- Add qrcode to footer
- Validate by LibreSign App
- Request sign by LibreSign App
- Resend sign invite email
- App config to configure JSignPDF
- Added integration with Approval app on README.md. Thanks to @eneiluj
- Endpoint to list LibreSign files
- Endpoint to attach files to LibreSign profile
- Entpoint to list all attached files to LibreSign profile
- Endpoints to delete signer and file sign request
- One more step to turn possible replace CFSSL
- Test for validation of Swagger documentation
- GitHub action to add a changelog reminder

# Changed
- Bump max Nextcloud version to 23
- Increment of coverage on backend code
- Bug fixes and refactorings resulting from increased coverage
- Use name of user on error message when email is empty
- Logo replaced by new logo
- It will only verify the password if nextcloud requests confirmation of the password by the OC.
- Check if has pfx

# Removed
- Removed dsv folder
- Removed docs folder

# Fixed
- Rendering files tab in Nextcloud 20 and 21
- Invalid method name when validating if a file signature has already been requested
- Tests autoload
- Correction of loading class after clicking sign in application
- Add ellipsis to pdf file title
- Now it is possible to choose a file even if it is inside x folders
- Clear uuid field before returns

## 2.3.0 - 2021-05-22

### Added and changed

- Allow devtools in the development [#250](https://github.com/LibreSign/libresign/pull/250) @vinicios-gomes
- Add has signature file check [#248](https://github.com/LibreSign/libresign/pull/248) @vitormattos
- Setup changelog in github actions [#246](https://github.com/LibreSign/libresign/pull/246) @vinicios-gomes
- l10n: Correct spelling [#244](https://github.com/LibreSign/libresign/pull/244) @Valdnet
- Validation by route using UUID [#243](https://github.com/LibreSign/libresign/pull/243) @vinicios-gomes
- Add Home [#241](https://github.com/LibreSign/libresign/pull/241) @vinicios-gomes
- Remove unused file [#238](https://github.com/LibreSign/libresign/pull/238) @vitormattos
- Signature password [#236](https://github.com/LibreSign/libresign/pull/236) @vitormattos
- l10n: Correct text strings [#235](https://github.com/LibreSign/libresign/pull/235) @Valdnet
- Validation date format [#234](https://github.com/LibreSign/libresign/pull/234) @raw-vitor
- Update swagger [#233](https://github.com/LibreSign/libresign/pull/233) @vitormattos
- Add validation to yarn.lock [#232](https://github.com/LibreSign/libresign/pull/232) @raw-vitor
- Coverage improvement [#240](https://github.com/LibreSign/libresign/pull/240) @vitormattos
- Coverage improvement [#239](https://github.com/LibreSign/libresign/pull/239) @vitormattos
- Coverage improvement [#237](https://github.com/LibreSign/libresign/pull/237) @vitormattos
- Coverage improvement [#231](https://github.com/LibreSign/libresign/pull/231) @vitormattos
- Coverage improvement [#228](https://github.com/LibreSign/libresign/pull/228) @vitormattos
- Coverage improvement [#226](https://github.com/LibreSign/libresign/pull/226) @vitormattos
- Signature password [#225](https://github.com/LibreSign/libresign/pull/225) @vitormattos
- Create CONTRIBUTING.md [#224](https://github.com/LibreSign/libresign/pull/224) @vitormattos
- Create CODE_OF_CONDUCT.md [#223](https://github.com/LibreSign/libresign/pull/223) @vitormattos
- Signature validate [#221](https://github.com/LibreSign/libresign/pull/221) @raw-vitor
- File tab [#216](https://github.com/LibreSign/libresign/pull/216) @vinicios-gomes
- Improvements in validate file_id [#215](https://github.com/LibreSign/libresign/pull/215) @vitormattos
- translate text [#214](https://github.com/LibreSign/libresign/pull/214) @vinicios-gomes
- Change property name [#213](https://github.com/LibreSign/libresign/pull/213) @vitormattos
- Sign using nodeid [#212](https://github.com/LibreSign/libresign/pull/212) @vitormattos
- LibreSign signature validation [#206](https://github.com/LibreSign/libresign/pull/206) @vitormattos
- default placeholder url  [#204](https://github.com/LibreSign/libresign/pull/204) @raw-vitor
- merge main in signature validate [#203](https://github.com/LibreSign/libresign/pull/203) @vinicios-gomes
- l10n: Add a dot and an ellipsis [#202](https://github.com/LibreSign/libresign/pull/202) @Valdnet
- Return status signed [#200](https://github.com/LibreSign/libresign/pull/200) @vitormattos
- Disable create account button when submitting form [#198](https://github.com/LibreSign/libresign/pull/198) @raw-vitor
- Only use validate page if is defined [#197](https://github.com/LibreSign/libresign/pull/197) @vitormattos
- Force email to lowercase [#196](https://github.com/LibreSign/libresign/pull/196) @vitormattos
- disable-btn [#194](https://github.com/LibreSign/libresign/pull/194) @raw-vitor
- translations [#193](https://github.com/LibreSign/libresign/pull/193) @vitormattos
- Validates a PDF. Triggers error if invalid. [#186](https://github.com/LibreSign/libresign/pull/186) @vitormattos
- url validation field [#183](https://github.com/LibreSign/libresign/pull/183) @raw-vitor
- Remove simplify changelog [#181](https://github.com/LibreSign/libresign/pull/181) @vitormattos
- Test matrix [#177](https://github.com/LibreSign/libresign/pull/177) @vitormattos
- Add app:check-code [#176](https://github.com/LibreSign/libresign/pull/176) @vitormattos
- Make info.xml compatible with xml schema [#175](https://github.com/LibreSign/libresign/pull/175) @vitormattos

### Bugfix

- Fix mistake in WebhookService::getFileUser() [#252](https://github.com/LibreSign/libresign/pull/252) @eneiluj
- Fix message in the home page app [#247](https://github.com/LibreSign/libresign/pull/247) @vinicios-gomes
- Fix extraneous-import error [#229](https://github.com/LibreSign/libresign/pull/229) @vinicios-gomes
- Bugfix generate password [#227](https://github.com/LibreSign/libresign/pull/227) @vitormattos
- Improvements and bugfix [#199](https://github.com/LibreSign/libresign/pull/199) @vitormattos
- Fix transifex setting [#190](https://github.com/LibreSign/libresign/pull/190) @vitormattos
- Fix get config [#187](https://github.com/LibreSign/libresign/pull/187) @vitormattos
- Fix property name [#185](https://github.com/LibreSign/libresign/pull/185) @vitormattos
- Bugfix: concatenate [#184](https://github.com/LibreSign/libresign/pull/184) @vitormattos
- Help to cs:fix when fail [#179](https://github.com/LibreSign/libresign/pull/179) @vitormattos
- Fix fatal error on run in cron [#174](https://github.com/LibreSign/libresign/pull/174) @vitormattos
- Prevent warning [#182](https://github.com/LibreSign/libresign/pull/182) @vitormattos

## 2.2.0 - 2021-04-12

### Changed

- l10n: Add an apostrophe [#134](https://github.com/LibreSign/libresign/pull/134) @Valdnet
- Move settings to specific menu [#164](https://github.com/LibreSign/libresign/pull/164) @vitormattos
- Add callback url in examble of documentation [#160](https://github.com/LibreSign/libresign/pull/160) @vitormattos
- l10n: Change case of letter [#145](https://github.com/LibreSign/libresign/pull/145) @Valdnet
- Update info.xml [#126](https://github.com/LibreSign/libresign/pull/126) @vitormattos
- Update info xml [#128](https://github.com/LibreSign/libresign/pull/128) @vitormattos
- Custom validation site [#129](https://github.com/LibreSign/libresign/pull/129) @vitormattos
- l10n: Change to singular [#132](https://github.com/LibreSign/libresign/pull/132) @Valdnet
- l10n: Correct text string for login [#133](https://github.com/LibreSign/libresign/pull/133) @Valdnet
- l10n: Change to uppercase [#135](https://github.com/LibreSign/libresign/pull/135) @Valdnet
- Change text [#136](https://github.com/LibreSign/libresign/pull/136) @vitormattos
- l10n: Change order [#139](https://github.com/LibreSign/libresign/pull/139) @Valdnet
- l10n: Shorten message [#141](https://github.com/LibreSign/libresign/pull/141) @Valdnet
- l10n: Replace with adjective [#142](https://github.com/LibreSign/libresign/pull/142) @Valdnet
- l10n: Change to uppercase URI [#143](https://github.com/LibreSign/libresign/pull/143) @Valdnet
- Replace collection by list [#147](https://github.com/LibreSign/libresign/pull/147) @vitormattos
- Make validate endpoint public [#163](https://github.com/LibreSign/libresign/pull/163) @vitormattos
- l10n: Change message [#144] [#148](https://github.com/LibreSign/libresign/pull/148) @Valdnet
- l10n: Change error message of file [#150](https://github.com/LibreSign/libresign/pull/150) @Valdnet
- Api documentation [#152](https://github.com/LibreSign/libresign/pull/152) @vitormattos
- Improvement in text [#157](https://github.com/LibreSign/libresign/pull/157) @vitormattos
- Validate by UUID [#161](https://github.com/LibreSign/libresign/pull/161) @vitormattos
- bump setup php [#162](https://github.com/LibreSign/libresign/pull/162) @vitormattos
- Create user design color [#98](https://github.com/LibreSign/libresign/pull/98) @raw-vitor

### Fixed

- Change var name and fix translation [#149](https://github.com/LibreSign/libresign/pull/149) @vitormattos
- Fix definition [#151](https://github.com/LibreSign/libresign/pull/151) @vitormattos
- Fix text [#153](https://github.com/LibreSign/libresign/pull/153) @vitormattos
- Fix text [#154](https://github.com/LibreSign/libresign/pull/154) @vitormattos
- Fix text [#155](https://github.com/LibreSign/libresign/pull/155) @vitormattos
- Fix text [#156](https://github.com/LibreSign/libresign/pull/156) @vitormattos

## 2.1.2 - 2021-03-21

### Changed

- Changelog [#125](https://github.com/LibreSign/libresign/pull/125) @vitormattos
- Bump release [#117](https://github.com/LibreSign/libresign/pull/117) @vitormattos
- Backend translations [#116](https://github.com/LibreSign/libresign/pull/116) @vitormattos
- Review frontend translations [#115](https://github.com/LibreSign/libresign/pull/115) @vitormattos
- Only include line if necessary [#124](https://github.com/LibreSign/libresign/pull/124) @vitormattos
- Instructions to create cfssl folder [#123](https://github.com/LibreSign/libresign/pull/123) @vitormattos
- Update README.md [#122](https://github.com/LibreSign/libresign/pull/122) @vitormattos

### Fixed

- Fix transifex config [#119](https://github.com/LibreSign/libresign/pull/119) @vitormattos
- Fix langmap config [#118](https://github.com/LibreSign/libresign/pull/118) @MorrisJobke

## 2.0.5 - 2021-03-11

- Changelog [#114](https://github.com/LibreSign/libresign/pull/114) @vitormattos
- Add health check [#113](https://github.com/LibreSign/libresign/pull/113) @vitormattos
- Remove unused var [#111](https://github.com/LibreSign/libresign/pull/111) @vitormattos
- Catch error [#112](https://github.com/LibreSign/libresign/pull/112) @vitormattos
- Rename property [#110](https://github.com/LibreSign/libresign/pull/110) @vitormattos
- Add route me [#109](https://github.com/LibreSign/libresign/pull/109) @vitormattos
- Bump version [#108](https://github.com/LibreSign/libresign/pull/108) @vitormattos
- Bump packages [#107](https://github.com/LibreSign/libresign/pull/107) @vitormattos
- Update changelog [#103](https://github.com/LibreSign/libresign/pull/103) @vitormattos

## 2.0.4 - 2021-03-09

### Changed

- Add category [#106](https://github.com/LibreSign/libresign/pull/106) @vitormattos
- Fix app name, description and summary [#105](https://github.com/LibreSign/libresign/pull/105) @vitormattos

## 2.0.1 - 2021-03-08
- Makefile and change dependency repository [#102](https://github.com/LibreSign/libresign/pull/102) @vitormattos
- Clean package.json [#101](https://github.com/LibreSign/libresign/pull/101) @vitormattos

### Changed

- Feature publish app [#100](https://github.com/LibreSign/libresign/pull/100) @vitormattos
- Fix package size [#99](https://github.com/LibreSign/libresign/pull/99) @vitormattos
- Add automate generate changelog [#97](https://github.com/LibreSign/libresign/pull/97) @vitormattos
- Changelog workflow [#96](https://github.com/LibreSign/libresign/pull/96) @vitormattos
- Changelog workflow [#95](https://github.com/LibreSign/libresign/pull/95) @vitormattos
- External route [#22](https://github.com/LibreSign/libresign/pull/22) @vinicios-gomes

## 2.0.0 - 2021-02-25

### Added

- Add CSP [#91](https://github.com/LibreSign/libresign/pull/91) @vitormattos
- Inserting data in the store [#90](https://github.com/LibreSign/libresign/pull/90) @vinicios-gomes
- Send mail when change sign request [#89](https://github.com/LibreSign/libresign/pull/89) @vitormattos
- Feature update improvements [#86](https://github.com/LibreSign/libresign/pull/86) @vitormattos
- Route to get PDF [#85](https://github.com/LibreSign/libresign/pull/85) @vitormattos
- Redux Struture for persistence of data [#83](https://github.com/LibreSign/libresign/pull/83) @vinicios-gomes
- Validate user [#82](https://github.com/LibreSign/libresign/pull/82) @vitormattos
- Cancel sign notification [#81](https://github.com/LibreSign/libresign/pull/81) @vitormattos
- Delete sign request [#80](https://github.com/LibreSign/libresign/pull/80) @vitormattos
- Success default page [#79](https://github.com/LibreSign/libresign/pull/79) @vinicios-gomes
- Show error when user already signed the file [#77](https://github.com/LibreSign/libresign/pull/77) @vitormattos
- error handler [#76](https://github.com/LibreSign/libresign/pull/76) @vitormattos
- patch sign request and move description to relation beetwen user and file [#74](https://github.com/LibreSign/libresign/pull/74) @vitormattos
- Now it is possible to view the pdf [#69](https://github.com/LibreSign/libresign/pull/69) @vinicios-gomes
- Disable button if sign sucess, show toast error if has error message [#68](https://github.com/LibreSign/libresign/pull/68) @vinicios-gomes
- Translated texts and change in the error toast message. [#67](https://github.com/LibreSign/libresign/pull/67) @vinicios-gomes
- Improvement on error handling [#66](https://github.com/LibreSign/libresign/pull/66) @vitormattos
- Pass error message through route props [#62](https://github.com/LibreSign/libresign/pull/62) @vinicios-gomes
- Bump max nextloud version [#59](https://github.com/LibreSign/libresign/pull/59) @vitormattos
- Documentation [#57](https://github.com/LibreSign/libresign/pull/57) @vitormattos
- Feature notify callback [#56](https://github.com/LibreSign/libresign/pull/56) @vitormattos
- Feature add footer [#55](https://github.com/LibreSign/libresign/pull/55) @vitormattos
- Test libraries [#54](https://github.com/LibreSign/libresign/pull/54) @vinicios-gomes
- Route sign document [#51](https://github.com/LibreSign/libresign/pull/51) @vinicios-gomes
- Interaction with the api [#50](https://github.com/LibreSign/libresign/pull/50) @vinicios-gomes
- Rename field [#48](https://github.com/LibreSign/libresign/pull/48) @vitormattos
- Ident [#47](https://github.com/LibreSign/libresign/pull/47) @vitormattos
- Refactor rename field [#45](https://github.com/LibreSign/libresign/pull/45) @vitormattos
- Feature sign using uuuid [#41](https://github.com/LibreSign/libresign/pull/41) @vitormattos
- Return data to sign after create user [#40](https://github.com/LibreSign/libresign/pull/40) @vitormattos
- Feature account create [#39](https://github.com/LibreSign/libresign/pull/39) @vitormattos
- Feature send email [#35](https://github.com/LibreSign/libresign/pull/35) @vitormattos
- External route create user [#33](https://github.com/LibreSign/libresign/pull/33) @raw-vitor
- Webhook config front [#32](https://github.com/LibreSign/libresign/pull/32) @vinicios-gomes
- Feature add webhook [#30](https://github.com/LibreSign/libresign/pull/30) @vitormattos
- Translate [#28](https://github.com/LibreSign/libresign/pull/28) @vinicios-gomes
- External route [#27](https://github.com/LibreSign/libresign/pull/27) @vitormattos
- Mock config [#23](https://github.com/LibreSign/libresign/pull/23) @vitormattos
- Secutiry Policy for acessing data on an external route. [#21](https://github.com/LibreSign/libresign/pull/21) @vinicios-gomes
- Add badge and ajust position of itens in composer.json [#20](https://github.com/LibreSign/libresign/pull/20) @vitormattos
- Refactor bump package [#19](https://github.com/LibreSign/libresign/pull/19) @vitormattos
- Add php-cs check [#18](https://github.com/LibreSign/libresign/pull/18) @vitormattos
- GitHub actions [#17](https://github.com/LibreSign/libresign/pull/17) @vitormattos
- Change to jsignpdf without java [#16](https://github.com/LibreSign/libresign/pull/16) @vitormattos
- Add blank page [#15](https://github.com/LibreSign/libresign/pull/15) @vitormattos
- php_cs [#13](https://github.com/LibreSign/libresign/pull/13) @vitormattos
- Refactor [#11](https://github.com/LibreSign/libresign/pull/11) @vitormattos

### Fixed

- Fix Did not access the subscription page after creating the user and … [#92](https://github.com/LibreSign/libresign/pull/92) @vinicios-gomes
- Fix widthh description [#88](https://github.com/LibreSign/libresign/pull/88) @vinicios-gomes
- fix validate user [#84](https://github.com/LibreSign/libresign/pull/84) @vitormattos
- Fix invalid route [#63](https://github.com/LibreSign/libresign/pull/63) @vitormattos
- Fix much bugs [#49](https://github.com/LibreSign/libresign/pull/49) @vitormattos
- Fix admin permissions in routes. [#14](https://github.com/LibreSign/libresign/pull/14) @vitormattos
