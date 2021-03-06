<?php

/* This file is part of SilMod.
 *
 * SilMod is free software: you can redistribute it and/or modify it under the
 * terms of the GNU Lesser General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * SilMod is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see
 *
 *  http://www.gnu.org/licenses/
 *
 *
 * Copyright (C)
 *  2016 Alexander Haase <ahaase@alexhaase.de>
 */

namespace SilMod;

use Exception;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;


class SilMod extends Application
{
	/** \brief Constructor.
	 *
	 * \details Creates a new SilMod object and initializes all modules.
	 *
	 *
	 * \param options Option array.
	 */
	public function __construct(array $options = array())
	{
		/* Register the Silex error handler to convert errors into exceptions.
		 * This should help to code the modules in an easy way so you have to
		 * take care about exceptions only and not exceptions AND errors. */
		ErrorHandler::register();


		/* Initialize Silex and register all required frameworks for the basic
		 * SilMod infrastructure. */
		parent::__construct();

		$this->error($this->json_error_handler());

		$this->register(new TwigServiceProvider(), array('twig.options' =>
		                isset($options['twig']) ? $options['twig'] : array()));


		/* Register all modules. */
		if (isset($options['modules']['path']))
			$this->load_modules($this->toArray($options['modules']['path']));
	}


	/** \brief Return a JSON error handler for Silex.
	 *
	 * \details Usually the Silex exception handler will return a HTML page with
	 *  the error message and a backtrace, if debug options are enabled. This
	 *  function returns an error handler which will return the exceptions error
	 *  message as JSON, so it may be processed by the client (e.g. for API
	 *  usage). If the client does not accept JSON, the error handler will do
	 *  nothing and the default error handler will return the error message as
	 *  HTML.
	 *
	 *
	 * \return The JSON error handler.
	 */
	private function json_error_handler() {
		return function (Exception $e, Request $request, $code) {
			$accept = $request->headers->get('Accept');
			if (strpos($accept, 'json') !== false)
				return $this->json(array(
					'status' => 'error',
					'message' => str_replace('"', '', $e->getMessage())
				), $code);
		};
	}


	/** \brief Convert \p src to an array.
	 *
	 * \details Some functions may require parameters as an array. This function
	 *  will return either \p src unchanged, if it is an array, or an array with
	 *  \p src as the only element inside of it.
	 *
	 *
	 * \param src The data to be converted.
	 *
	 * \return The converted array.
	 */
	protected function toArray($src)
	{
		if (is_array($src))
			return $src;

		return array($src);
	}


	/** \brief Load all files called autoload.php in \p paths as modules.
	 *
	 * \details This function iterates over all paths in \p paths and includes
	 *  any file named 'autoload.php' in the paths or any of its subdirectories.
	 *  Each module then will register itself by using the provided $app
	 *  variable.
	 *
	 *
	 * \param paths Array of paths containing all required modules.
	 */
	private function load_modules(array $paths)
	{
		/* Propagate the $this as the $app variable, so the modules may register
		 * themselves. */
		$app = $this;

		foreach ($paths as $path) {
			/* Recurse into subdirectories. */
			$sub = glob("$path/[^.]*", GLOB_ONLYDIR);
			if (!empty($sub))
				$this->load_modules($sub);

			/* Load autoload.php if file is available. */
			if (file_exists("$path/autoload.php"))
				require_once "$path/autoload.php";
		}
	}


	/** \brief Add \p path in twig namespace \p name.
	 *
	 * \details This function adds \p path to the list of template paths for
	 *  twig. They may be used in the modules in a separate namespace \p name.
	 *
	 *
	 * \param name Module name.
	 * \param path The path to be added for twig.
	 */
	public function register_twig_path(string $name, string $path)
	{
		$this['twig.loader.filesystem']->addPath($path, $name);
	}
}

?>
