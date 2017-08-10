<?php

namespace Plugin\TownCMSInstaller;

use App\Models\Vhost;
use Illuminate\Support\Facades\DB;

class TownCMSManager
{
	private $vhost;

	public function __construct (Vhost $vhost)
	{
		$this->vhost = $vhost;
	}

	public function install ()
	{
		$username = $this->vhost->user->userInfo->username;
		$domain   = $this->vhost->servername;

		// Hard code path for local testing
		$docroot  = '/Directory/Directory/foo/'/*$this->vhost->docroot*/;

		// Get the newly created user from the domain
		$user = substr ($domain, 0, strrpos ($domain, "."));

		// Combine the username and user for the database name
		$dbUsername = $username . '_' . $user;

		define ('TOWN_CMS_GIT_REPO', 'gogs@git.webtown.ie:robinjacobs/town-cms.git');
		define ('SSH_KEY', '~/.ssh/penguincontrol_towncms_autoinstall');

		if (empty ($username) || empty ($domain))
			die ('Specify username and domain (Example: "php setup.php ctballjewellers ctballjewellers.town.ie")');
		if (strlen ($username) > 16 || strlen ($username) < 5)
			die ('Username needs to be between 5 and 16 characters long');

		echo 'Generating password and key...' . PHP_EOL;
		// Hard code password for local testing
		$password = 'password'/*bin2hex (openssl_random_pseudo_bytes (10))*/
		;
		/*$passwordCrypt = password_hash ($password, PASSWORD_DEFAULT);*/
		$key = bin2hex (openssl_random_pseudo_bytes (32));

		// Create the database and add the correct privileges
		$pdo = DB::connection ()->getPdo ();
		echo 'Connecting to database...' . PHP_EOL;
		$pdo->setAttribute (\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		echo 'Creating database user and granting privileges...' . PHP_EOL;
		$pdo->exec ("CREATE DATABASE `$dbUsername`;");
		$pdo->exec ("GRANT ALL PRIVILEGES ON `$dbUsername`.* TO '$username'@'%';");

		if ( ! chdir ($docroot))
			die ("Could not switch directory...");
		echo 'Switched to path ' . $docroot . PHP_EOL;

		// Clone down the town-cms to the correct folder
		echo 'Cloning Git repository...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('GIT_SSH_COMMAND="ssh -i ' . escapeshellarg (SSH_KEY) . '" git clone ' . escapeshellarg (TOWN_CMS_GIT_REPO), $output, $exitCode);
		if ($exitCode !== 0)
			die ('Cloning Git repository failed...' . PHP_EOL . implode (PHP_EOL, $output));

		if ( ! chdir ($docroot . '/town-cms'))
			die ("Could not switch directory...");
		echo 'Switched to path ' . $docroot . '/town-cms' . PHP_EOL;

		// Install the dependencies necessary to get the app running
		echo 'Installing dependencies...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('composer install', $output, $exitCode);
		if ($exitCode !== 0)
			die ('`composer install` failed...' . PHP_EOL . implode (PHP_EOL, $output));

		// Copy the .env file and populate with the correct variables
		echo 'Setting up the CMS...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('cp .env.example .env', $output, $exitCode);
		if ($exitCode !== 0)
			die ('Copying .env file failed...' . PHP_EOL . implode (PHP_EOL, $output));

		echo 'Generate application key...' . PHP_EOL;
		unset ($output, $exitCode);
		$appKey = exec ('php artisan key:generate', $output, $exitCode);
		if ($exitCode !== 0)
			die ('`php artisan key:generate` failed...' . PHP_EOL . implode (PHP_EOL, $output));

		// Have to get app key from command string as it does not import into
		// .env file correctly
		$appKey = substr ($appKey, 17, -19);

		// Create array of variables that will be used to seed the .env file
		$sedMap = [
			'{:APP_KEY:}'  => $appKey,
			'{:DOMAIN:}'   => $domain,
			'{:TOWN_KEY:}' => $key,
			'{:DATABASE:}' => $dbUsername,
			'{:USERNAME:}' => $username,
			'{:PASSWORD:}' => $password
		];

		echo 'Writing to .env file...' . PHP_EOL;
		if (file_exists ('.env'))
		{
			if ( ! $this->changeEnv ($sedMap))
				die ('Could not write to file' . PHP_EOL . implode (PHP_EOL, $output));
		}
		else
		{
			die ('Could not find file' . PHP_EOL . implode (PHP_EOL, $output));
		}

		// Use the database file in the etc folder within the cms to seed the database with the correct tables
		echo 'Writing to database...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('mysql -u root --password=' . $password . ' ' . $dbUsername . ' < etc/database.sql', $output, $exitCode);
		if ($exitCode !== 0)
			die ('Database seed failed...' . PHP_EOL . implode (PHP_EOL, $output));

		// Run laravel migrations to generate any other tables.
		// Not updating the correct database probably for the same reason app key doesn't install correctly
		echo 'Updating CMS...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('php artisan cms:update', $output, $exitCode);
		if ($exitCode !== 0)
			die ('CMS update failed...' . PHP_EOL . implode (PHP_EOL, $output));

		// Commented out for local testing

		/*echo 'Generating vHost...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('sudo cp /etc/apache2/sites-available/template.conf ' . escapeshellarg ('/etc/apache2/sites-available/' . $user . '.conf'), $output, $exitCode);
		if ($exitCode !== 0)
			die ('Copying vHost template failed...' . PHP_EOL . implode (PHP_EOL, $output));

		foreach ($sedMap as $placeholder => $value) {
			$sedStr = 's/' . $placeholder . '/' . $value . '/g';

			unset ($output, $exitCode);
			exec ('sudo sed -i ' . escapeshellarg ($sedStr) . ' ' . escapeshellarg ('/etc/apache2/sites-available/' . $user . '.conf'), $output, $exitCode);
			if ($exitCode !== 0)
				die ('Placeholder replacement with `sed` failed...' . PHP_EOL . implode (PHP_EOL, $output));
		}

		echo 'Enabling vHost...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('sudo a2ensite ' . escapeshellarg ($user), $output, $exitCode);
		if ($exitCode !== 0)
			die ('Enabling vHost failed...' . PHP_EOL . implode (PHP_EOL, $output));

		echo 'Testing Apache configuration...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('sudo apache2ctl configtest', $output, $exitCode);
		if ($exitCode !== 0)
			die ('Configuration test failed...' . PHP_EOL . implode (PHP_EOL, $output));

		echo 'Reloading Apache configuration...' . PHP_EOL;
		unset ($output, $exitCode);
		exec ('sudo systemctl reload apache2', $output, $exitCode);
		if ($exitCode !== 0)
			die ('Reloading Apache failed...' . PHP_EOL . implode (PHP_EOL, $output));*/


		echo PHP_EOL . '---' . PHP_EOL;
		echo 'Setup completed!' . PHP_EOL;
		echo '---' . PHP_EOL;
		echo 'Website: http://' . $domain . '/' . PHP_EOL;
		echo 'Username: ' . $username . PHP_EOL;
		echo 'Password: ' . $password . PHP_EOL;
		echo 'Key: ' . $key . PHP_EOL;
		echo '---' . PHP_EOL;

		return array
		(
			'exitcode' => $exitCode,
			'output'   => implode (PHP_EOL, $output)
		);
	}

	/**
	 * Method to populate all the correct variables into the .env file
	 *
	 * @param $data array of variables
	 *
	 * @return bool whether the file was correctly seeded
	 */
	protected function changeEnv ($data)
	{
		if (count ($data) > 0)
		{

			// Read .env file
			$env = file_get_contents ('.env');

			$env = str_replace (array_keys ($data), array_values ($data), $env);

			// And overwrite the .env with the new data
			file_put_contents ('.env', $env);

			return TRUE;
		}
		else
			return FALSE;
	}
}


















