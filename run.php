<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class SoftwareVersions {

	protected $labelVersionNotFound = 'error';

	protected $packages = [];

	protected $config = [];

	protected $htmlCache = [];

	public function run() {
		$parser = new Parser();
		$this->config = $parser->parseFile('configuration.yaml');

		foreach ($this->config['packages'] as $package => $sources) {
			foreach ($sources as $sourceName => $sourceConfig) {
				$this->packages[$package][$sourceName] = [];
				switch ($sourceName) {
					case "git":
						$this->getGitVersion($package);
						break;
					case "pecl":
						$this->getPeclVersions($package);
						break;
					case "debian":
						$this->getDebianVersions($package);
						break;
					case "ppa":
						$this->getPpaVersions($package);
						break;
					case "dpa":
						$this->getDpaVersions($package);
						break;
					case "upstream":
						$this->getUpstreamVersion($package);
				}
			}
		}

		echo Yaml::dump($this->packages);
	}

	protected function getGitVersion(String $package) {
		$url = $this->config['packages'][$package]['git']['url'];
		$tagRegex = $this->config['packages'][$package]['git']['tagRegex'];

		$command = sprintf("git ls-remote --tags %s | cut -f2 | grep -o '%s' | sort -V | tail -n 1", $url, $tagRegex);
		$version = self::runCommand($command)[0];
		$this->packages[$package]['git'] = !empty($version) ? $version : $this->labelVersionNotFound;
	}

	protected function getPeclVersions(String $package) {
		$packageName = $this->config['packages'][$package]['pecl']['name'];

		$command = sprintf("pecl search %s | grep ^%s | awk '{print $2}'", $packageName, $packageName);
		$versions = self::runCommand($command);
		$this->packages[$package]['pecl'] = count($versions) > 0 ? $versions : $this->labelVersionNotFound;
	}

	protected function getDebianVersions(String $package) {
		$packageName = $this->config['packages'][$package]['debian']['name'];

		// API: https://sources.debian.org/doc/api/
		$url = sprintf('https://sources.debian.org/api/src/%s/', $packageName);
		$jsonData = file_get_contents($url);
		$data = json_decode($jsonData, true);

		foreach ($data['versions'] as $version) {
			foreach ($version['suites'] as $suite) {
				$this->packages[$package]['debian'][$suite][] = $version['version'];
			}
		}

		if (!is_array($this->packages[$package]['debian'])) {
			$this->packages[$package]['debian'] = $this->labelVersionNotFound;
		}
	}

	public function getPpaVersions(String $package) {
		$packageName = $this->config['packages'][$package]['ppa']['name'];

		$url = $this->config['packages'][$package]['ppa']['url'];
		$query = sprintf("%s/+packages?field.name_filter=%s&field.status_filter=published&field.series_filter=", $url, $packageName);
		$html = file_get_contents($query);
		$doc = new DOMDocument();
		$doc->loadHTML($html);
		$nodeList = $doc->getElementsByTagName('a');

		foreach ($nodeList as $node) {
			if (is_integer(strpos($node->getAttribute('href'), '+sourcepub'))) {
				$this->packages[$package]['ppa'][] = str_replace($packageName . ' - ', '', trim($node->textContent));
			}
		}

		if (!is_array($this->packages[$package]['ppa'])) {
			$this->packages[$package]['ppa'] = $this->labelVersionNotFound;
		}
	}

	public function getDpaVersions(String $package) {
		$packageName = $this->config['packages'][$package]['dpa']['name'];
		$releases = $this->config['sources']['dpa']['releases'];
		$baseUrl = $this->config['packages'][$package]['dpa']['url'];

		$packagePattern = sprintf('Package: %s', $packageName);
		$versionPattern = sprintf('/Version: ([^\n]+)/', $packageName);

		foreach ($releases as $release) {
			$url = sprintf('%s/dists/%s/main/binary-amd64/Packages', $baseUrl, $release);

			if (isset($this->htmlCache[$url])) {
				$content = $this->htmlCache[$url];
			} else {
				$content = file_get_contents($url);
				$this->htmlCache[$url] = $content;
			}

			$packages = explode(PHP_EOL . PHP_EOL, $content);
			foreach ($packages as $item) {
				if (is_integer(strpos($item, $packagePattern))) {
					preg_match($versionPattern, $item, $releaseVersion);
					$this->packages[$package]['dpa'][] = $releaseVersion[1];
					break;
				}
			}
		}

		if (!is_array($this->packages[$package]['dpa'])) {
			$this->packages[$package]['dpa'] = $this->labelVersionNotFound;
		}
	}

	protected function getUpstreamVersion(String $package) {
		$path = $this->config['packages']['package']['upstream']['path'];
		$command = sprintf('uscan %s 2&>1 | egrep "^uscan: Newest version" | grep -o "[0-9]*\.[0-9]*\.[0-9]*" | tail -n 1', $path);
		$version = self::runCommand($command)[0];
		$this->packages[$package]['upstream'] = !empty($version) ? $version : $this->labelVersionNotFound;
	}

	protected static function runCommand(String $command): array {
		$output = [];
		exec($command, $output);
		return $output;
	}

}

$softwareVersions = new SoftwareVersions();
$softwareVersions->run();
