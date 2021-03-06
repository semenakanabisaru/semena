<?php
	define('_C_REQUIRES', true);

	require SYS_LIBS_PATH . 'lib.php';

	require SYS_KERNEL_PATH . 'subsystems/buffers/interfaces.php';
	require SYS_KERNEL_PATH . 'patterns/interfaces.php';
	require SYS_KERNEL_PATH . 'subsystems/models/hierarchy/interfaces.php';
	require SYS_KERNEL_PATH . 'subsystems/models/data/interfaces.php';
	require SYS_KERNEL_PATH . 'subsystems/models/events/interfaces.php';
	require SYS_KERNEL_PATH . 'subsystems/messages/interfaces.php';
	require SYS_KERNEL_PATH . 'utils/translators/interfaces.php';

	if(get_magic_quotes_gpc()) {
		require SYS_LIBS_PATH . 'security.php';
	}
	
	require SYS_LIBS_PATH . 'system.php';
	require SYS_LIBS_PATH . 'def_macroses.php';
	require SYS_LIBS_PATH . 'autoload.php';
	require SYS_LIBS_PATH . 'uuid.php';
	
	require SYS_KERNEL_PATH . 'patterns/singletone.php';
	require SYS_KERNEL_PATH . 'patterns/umiEntinty.php';

	require SYS_KERNEL_PATH . 'entities/exceptions/baseException.php';
	require SYS_KERNEL_PATH . 'entities/exceptions/coreException.php';
	require SYS_KERNEL_PATH . 'entities/exceptions/databaseException.php';
	require SYS_KERNEL_PATH . 'entities/exceptions/privateException.php';
	require SYS_KERNEL_PATH . 'entities/exceptions/publicException.php';

	require SYS_KERNEL_PATH . 'subsystems/database/ConnectionPool.php';
	require SYS_KERNEL_PATH . 'subsystems/database/IConnection.php';
	require SYS_KERNEL_PATH . 'subsystems/database/IQueryResult.php';
	require SYS_KERNEL_PATH . 'subsystems/database/mysqlConnection.php';
	require SYS_KERNEL_PATH . 'subsystems/database/mysqlQueryResult.php';

	require SYS_DEF_MODULE_PATH . 'def_module.php';

	require SYS_KERNEL_PATH . "utils/translators/translatorWrapper.php";
	require SYS_LIBS_PATH . 'streams.php';
?>