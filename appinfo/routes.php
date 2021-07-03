<?php

return [
	'routes' => [
		// API
		['name' => 'api#preflighted_cors',            'url' => '/api/0.1/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+'], ],

		// Deprecated
		['name' => 'accountDeprecated#me',            'url' => '/api/0.1/webhook/me', 'verb' => 'GET'],

		['name' => 'account#createToSign',            'url' => '/api/0.1/account/create/{uuid}', 'verb' => 'POST'],
		['name' => 'account#me',                      'url' => '/api/0.1/account/me', 'verb' => 'GET'],
		['name' => 'account#signatureGenerate',       'url' => '/api/0.1/account/signature', 'verb' => 'POST'],
		['name' => 'account#addFiles',                'url' => '/api/0.1/account/files', 'verb' => 'POST'],
		['name' => 'account#approvalList',            'url' => '/api/0.1/account/files/approval/list', 'verb' => 'GET'],

		// Deprecated
		['name' => 'signFileDeprecated#requestSign',  'url' => '/api/0.1/webhook/register', 'verb' => 'POST'],
		['name' => 'signFileDeprecated#updateSign',   'url' => '/api/0.1/webhook/register', 'verb' => 'PATCH'],
		['name' => 'signFileDeprecated#removeSign',   'url' => '/api/0.1/webhook/register/signature', 'verb' => 'DELETE'],
		['name' => 'signFileDeprecated#sign',         'url' => '/api/0.1/sign', 'verb' => 'POST'],

		['name' => 'signFile#requestSign',            'url' => '/api/0.1/sign/register', 'verb' => 'POST'],
		['name' => 'signFile#updateSign',             'url' => '/api/0.1/sign/register', 'verb' => 'PATCH'],
		['name' => 'signFile#removeSign',             'url' => '/api/0.1/sign/register/signature', 'verb' => 'DELETE'],
		['name' => 'signFile#signUsingUuid',          'url' => '/api/0.1/sign/uuid/{uuid}', 'verb' => 'POST'],
		['name' => 'signFile#signUsingFileid',        'url' => '/api/0.1/sign/file_id/{fileId}', 'verb' => 'POST'],

		['name' => 'libresign#list',                  'url' => '/api/0.1/file/list', 'verb' => 'GET'],
		['name' => 'libresign#validateUuid',          'url' => '/api/0.1/file/validate/uuid/{uuid}', 'verb' => 'GET'],
		['name' => 'libresign#validateFileId',        'url' => '/api/0.1/file/validate/file_id/{fileId}', 'verb' => 'GET'],

		// Deprecated
		['name' => 'settingDeprecated#hasRootCert',   'url' => '/api/0.1/signature/has-root-cert', 'verb' => 'GET'],

		['name' => 'setting#hasRootCert',             'url' => '/api/0.1/setting/has-root-cert', 'verb' => 'GET'],
		// Admin config
		['name' => 'admin#generateCertificate',       'url' => '/api/0.1/admin/certificate', 'verb' => 'POST'],
		['name' => 'admin#loadCertificate',           'url' => '/api/0.1/admin/certificate', 'verb' => 'GET'],

		// Pages - restricted
		['name' => 'page#index',                      'url' => '/', 'verb' => 'GET'],
		['name' => 'page#getPdfUser',                 'url' => '/pdf/user/{uuid}', 'verb' => 'GET'],
		['name' => 'page#resetPassword',              'url' => '/reset-password', 'verb' => 'GET'],
		// Pages - public
		['name' => 'page#sign',                       'url' => '/sign/{uuid}', 'verb' => 'GET'],
		['name' => 'page#validation',                 'url' => '/validation', 'verb' => 'GET'],
		['name' => 'page#validationFile',             'url' => '/validation/{uuid}', 'verb' => 'GET'],
		['name' => 'page#getPdf',                     'url' => '/pdf/{uuid}', 'verb' => 'GET']
	],
];
