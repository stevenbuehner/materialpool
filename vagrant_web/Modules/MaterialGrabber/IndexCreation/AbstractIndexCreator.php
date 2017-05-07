<?php
/**
 * This file was created by  steven
 * Created: 16.07.16 17:22
 * All Rights reserved. No usage without written permission allowed.
 */

namespace Modules\MaterialGrabber\IndexCreation;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractIndexCreator {

	/** @var  boolean */
	protected $onlineVersion;
	/** @var  boolean */
	protected $splitOutputToMultipleFiles;
	/** @var  string */
	protected $mainOutputFile;
	/** @var  string[] */
	protected $grabberNamesToUse;
	/** @var  int[] */
	protected $grabberIdsToUse;
	/** @var  string[] */
	protected $indexTypes;

	abstract public function cleanupBevoreAnythingElse(InputInterface $input, OutputInterface $output);

	abstract public function writeMainIndex(OutputInterface $output);

	abstract public function writeDetailIndex(OutputInterface $output);

	/**
	 * @return boolean
	 */
	public function isOnlineVersion() {
		return $this->onlineVersion;
	}

	/**
	 * @param boolean $onlineVersion
	 */
	public function setOnlineVersion($onlineVersion) {
		$this->onlineVersion = $onlineVersion;
	}

	/**
	 * @return boolean
	 */
	public function isSplitOutputToMultipleFiles() {
		return $this->splitOutputToMultipleFiles;
	}

	/**
	 * @param boolean $splitOutputToMultipleFiles
	 */
	public function setSplitOutputToMultipleFiles($splitOutputToMultipleFiles) {
		$this->splitOutputToMultipleFiles = $splitOutputToMultipleFiles;
	}

	/**
	 * @return string
	 */
	public function getMainOutputFile() {
		return $this->mainOutputFile;
	}

	/**
	 * @param string $mainOutputFile
	 */
	public function setMainOutputFile($mainOutputFile) {
		$this->mainOutputFile = $mainOutputFile;
	}

	/**
	 * @return \string[]
	 */
	public function getGrabberNamesToUse() {
		return $this->grabberNamesToUse;
	}

	/**
	 * @param \string[] $grabberNamesToUse
	 */
	public function setGrabberNamesToUse($grabberNamesToUse) {
		$this->grabberNamesToUse = $grabberNamesToUse;
	}

	/**
	 * @return \int[]
	 */
	public function getGrabberIdsToUse() {
		return $this->grabberIdsToUse;
	}

	/**
	 * @param \int[] $grabberIdsToUse
	 */
	public function setGrabberIdsToUse($grabberIdsToUse) {
		$this->grabberIdsToUse = $grabberIdsToUse;
	}

	/**
	 * @return \string[]
	 */
	public function getIndexTypes() {
		return $this->indexTypes;
	}

	/**
	 * @param \string[] $indexTypes
	 */
	public function setIndexTypes($indexTypes) {
		$this->indexTypes = $indexTypes;
	}

	public function writeHtmlHead($outputFile) {
		$bibleImg     = base64_encode("<svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\"
	 width=\"45.844px\" height=\"45.844px\" viewBox=\"0 0 45.844 45.844\" style=\"enable-background:new 0 0 45.844 45.844;\"
	 xml:space=\"preserve\">
<g>
	<g>
		<path d=\"M39.454,39.057c0.804-0.896,1.072-2.035,0.695-3.234c-0.015-0.045-3.483-11.504-3.483-11.504
			c-0.377-1.248-1.525-2.08-2.83-2.08h-4.535v5.824c0,3.529-2.881,6.4-6.411,6.4c-3.529,0-6.412-2.871-6.412-6.4v-5.824h-3.67
			c-1.303,0-2.453,0.826-2.828,2.076c-2.766,9.625-2.795,9.727-3.942,13.082c-0.269,0.789-0.548,1.625-0.548,2.51
			c0,3.259,2.65,5.938,5.908,5.938H37.41c1.205,0,2.291-0.762,2.74-1.881c0.449-1.12,0.127-2.368-0.695-3.249
			C39.123,40.359,38.896,39.682,39.454,39.057z M37.409,42.779H11.398c-1.632,0-2.955-1.319-2.955-2.95
			c0-1.632,1.323-2.95,2.955-2.95H37.41C35.823,38.467,35.823,41.305,37.409,42.779z\"/>
		<path d=\"M14.545,14.862h4.884v13.201c0,1.903,1.558,3.446,3.461,3.446s3.461-1.543,3.461-3.446V14.862h4.921
			c1.903,0,3.446-1.557,3.446-3.46c0-1.904-1.543-3.461-3.446-3.461h-4.921V3.447C26.352,1.543,24.793,0,22.89,0
			s-3.461,1.543-3.461,3.447v4.494h-4.884c-1.903,0-3.446,1.558-3.446,3.461C11.099,13.305,12.643,14.862,14.545,14.862z\"/>
	</g>
</g>
</svg>");
		$arrowRight   = base64_encode("<svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\"
	 width=\"444.819px\" height=\"444.819px\" viewBox=\"0 0 444.819 444.819\" style=\"enable-background:new 0 0 444.819 444.819;\"
	 xml:space=\"preserve\">
<g>
	<path d=\"M434.252,114.203l-21.409-21.416c-7.419-7.04-16.084-10.561-25.975-10.561c-10.095,0-18.657,3.521-25.7,10.561
		L222.41,231.549L83.653,92.791c-7.042-7.04-15.606-10.561-25.697-10.561c-9.896,0-18.559,3.521-25.979,10.561l-21.128,21.416
		C3.615,121.436,0,130.099,0,140.188c0,10.277,3.619,18.842,10.848,25.693l185.864,185.865c6.855,7.23,15.416,10.848,25.697,10.848
		c10.088,0,18.75-3.617,25.977-10.848l185.865-185.865c7.043-7.044,10.567-15.608,10.567-25.693
		C444.819,130.287,441.295,121.629,434.252,114.203z\"/>
</g>
</svg>");
		$arrowDown    = base64_encode("<svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\"
	 width=\"444.819px\" height=\"444.819px\" viewBox=\"0 0 444.819 444.819\" style=\"enable-background:new 0 0 444.819 444.819;\"
	 xml:space=\"preserve\">
<g>
	<path d=\"M352.025,196.712L165.884,10.848C159.029,3.615,150.469,0,140.187,0c-10.282,0-18.842,3.619-25.697,10.848L92.792,32.264
		c-7.044,7.043-10.566,15.604-10.566,25.692c0,9.897,3.521,18.56,10.566,25.981l138.753,138.473L92.786,361.168
		c-7.042,7.043-10.564,15.604-10.564,25.693c0,9.896,3.521,18.562,10.564,25.98l21.7,21.413
		c7.043,7.043,15.612,10.564,25.697,10.564c10.089,0,18.656-3.521,25.697-10.564l186.145-185.864
		c7.046-7.423,10.571-16.084,10.571-25.981C362.597,212.321,359.071,203.755,352.025,196.712z\"/>
</g>
</svg>");
		$downloadIcon = base64_encode("<svg version=\"1.1\" id=\"Capa_1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\"
	 viewBox=\"0 0 29.978 29.978\" style=\"enable-background:new 0 0 29.978 29.978;\" xml:space=\"preserve\">
<g>
	<path d=\"M25.462,19.105v6.848H4.515v-6.848H0.489v8.861c0,1.111,0.9,2.012,2.016,2.012h24.967c1.115,0,2.016-0.9,2.016-2.012
		v-8.861H25.462z\"/>
	<path d=\"M14.62,18.426l-5.764-6.965c0,0-0.877-0.828,0.074-0.828s3.248,0,3.248,0s0-0.557,0-1.416c0-2.449,0-6.906,0-8.723
		c0,0-0.129-0.494,0.615-0.494c0.75,0,4.035,0,4.572,0c0.536,0,0.524,0.416,0.524,0.416c0,1.762,0,6.373,0,8.742
		c0,0.768,0,1.266,0,1.266s1.842,0,2.998,0c1.154,0,0.285,0.867,0.285,0.867s-4.904,6.51-5.588,7.193
		C15.092,18.979,14.62,18.426,14.62,18.426z\"/>
</g>
</svg>
");
		$headHtml     = "<head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>
        <style type=\"text/css\">
         body {
            font-family: 'PT Sans', serif, Georgia, \"Times New Roman\", Times;
        }

        h1, h2, h3 {
            margin-bottom: 0.2em;
        }

        .bookIndex, .indexIntend, h1 {
            padding-left: 40px;
        }

        .bookIndex, .intendIndex, .materialListe{
            margin: 0 0 1em 0;
        }

        li {
            list-style-type: none;
        }

        ul > ul {
            padding-bottom: 1em;
            padding-left: 1.5em;
        }

        li.bibelbuch {
            list-style-type: none;
            font-size: 1.5em;
            padding-top: 0.5em;
            padding-bottom: 0.3em;
            font-weight: bold;
        }
        
        li.bibelstelle{
            font-weight: bold;
        }

        .bible {
            background-size: contain;
            height: 1em;
            width: 1em;
            display: block;
            float: left;
            padding-right: 0.5em;
        }

        .material {
            cursor: pointer;
        }

        .material .arrow {
            width: 0.7em;
            height: 0.7em;
            display: block;
            float: left;
            margin-top: 0.2em;
            padding-right: 0.2em;
        }

        .desc {
            font-size: 0.9em;
            color: #888;
            padding: 0.2em;
            margin-bottom: 0.5em;
            margin-left: 1em;
            border-style: dotted;
            border-width: 1px;
        }

        .material.closed .desc {
            display: none;
        }

        .bible {
            background: url('data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHdpZHRoPSI0NS44NDRweCIgaGVpZ2h0PSI0NS44NDRweCIgdmlld0JveD0iMCAwIDQ1Ljg0NCA0NS44NDQiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDQ1Ljg0NCA0NS44NDQ7IgoJIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik0zOS40NTQsMzkuMDU3YzAuODA0LTAuODk2LDEuMDcyLTIuMDM1LDAuNjk1LTMuMjM0Yy0wLjAxNS0wLjA0NS0zLjQ4My0xMS41MDQtMy40ODMtMTEuNTA0CgkJCWMtMC4zNzctMS4yNDgtMS41MjUtMi4wOC0yLjgzLTIuMDhoLTQuNTM1djUuODI0YzAsMy41MjktMi44ODEsNi40LTYuNDExLDYuNGMtMy41MjksMC02LjQxMi0yLjg3MS02LjQxMi02LjR2LTUuODI0aC0zLjY3CgkJCWMtMS4zMDMsMC0yLjQ1MywwLjgyNi0yLjgyOCwyLjA3NmMtMi43NjYsOS42MjUtMi43OTUsOS43MjctMy45NDIsMTMuMDgyYy0wLjI2OSwwLjc4OS0wLjU0OCwxLjYyNS0wLjU0OCwyLjUxCgkJCWMwLDMuMjU5LDIuNjUsNS45MzgsNS45MDgsNS45MzhIMzcuNDFjMS4yMDUsMCwyLjI5MS0wLjc2MiwyLjc0LTEuODgxYzAuNDQ5LTEuMTIsMC4xMjctMi4zNjgtMC42OTUtMy4yNDkKCQkJQzM5LjEyMyw0MC4zNTksMzguODk2LDM5LjY4MiwzOS40NTQsMzkuMDU3eiBNMzcuNDA5LDQyLjc3OUgxMS4zOThjLTEuNjMyLDAtMi45NTUtMS4zMTktMi45NTUtMi45NQoJCQljMC0xLjYzMiwxLjMyMy0yLjk1LDIuOTU1LTIuOTVIMzcuNDFDMzUuODIzLDM4LjQ2NywzNS44MjMsNDEuMzA1LDM3LjQwOSw0Mi43Nzl6Ii8+CgkJPHBhdGggZD0iTTE0LjU0NSwxNC44NjJoNC44ODR2MTMuMjAxYzAsMS45MDMsMS41NTgsMy40NDYsMy40NjEsMy40NDZzMy40NjEtMS41NDMsMy40NjEtMy40NDZWMTQuODYyaDQuOTIxCgkJCWMxLjkwMywwLDMuNDQ2LTEuNTU3LDMuNDQ2LTMuNDZjMC0xLjkwNC0xLjU0My0zLjQ2MS0zLjQ0Ni0zLjQ2MWgtNC45MjFWMy40NDdDMjYuMzUyLDEuNTQzLDI0Ljc5MywwLDIyLjg5LDAKCQkJcy0zLjQ2MSwxLjU0My0zLjQ2MSwzLjQ0N3Y0LjQ5NGgtNC44ODRjLTEuOTAzLDAtMy40NDYsMS41NTgtMy40NDYsMy40NjFDMTEuMDk5LDEzLjMwNSwxMi42NDMsMTQuODYyLDE0LjU0NSwxNC44NjJ6Ii8+Cgk8L2c+CjwvZz4KPC9zdmc+') no-repeat top left;
            background-size: contain;
        }

        .material.closed .arrow {
            background: url('data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHdpZHRoPSI0NDQuODE5cHgiIGhlaWdodD0iNDQ0LjgxOXB4IiB2aWV3Qm94PSIwIDAgNDQ0LjgxOSA0NDQuODE5IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA0NDQuODE5IDQ0NC44MTk7IgoJIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxwYXRoIGQ9Ik0zNTIuMDI1LDE5Ni43MTJMMTY1Ljg4NCwxMC44NDhDMTU5LjAyOSwzLjYxNSwxNTAuNDY5LDAsMTQwLjE4NywwYy0xMC4yODIsMC0xOC44NDIsMy42MTktMjUuNjk3LDEwLjg0OEw5Mi43OTIsMzIuMjY0CgkJYy03LjA0NCw3LjA0My0xMC41NjYsMTUuNjA0LTEwLjU2NiwyNS42OTJjMCw5Ljg5NywzLjUyMSwxOC41NiwxMC41NjYsMjUuOTgxbDEzOC43NTMsMTM4LjQ3M0w5Mi43ODYsMzYxLjE2OAoJCWMtNy4wNDIsNy4wNDMtMTAuNTY0LDE1LjYwNC0xMC41NjQsMjUuNjkzYzAsOS44OTYsMy41MjEsMTguNTYyLDEwLjU2NCwyNS45OGwyMS43LDIxLjQxMwoJCWM3LjA0Myw3LjA0MywxNS42MTIsMTAuNTY0LDI1LjY5NywxMC41NjRjMTAuMDg5LDAsMTguNjU2LTMuNTIxLDI1LjY5Ny0xMC41NjRsMTg2LjE0NS0xODUuODY0CgkJYzcuMDQ2LTcuNDIzLDEwLjU3MS0xNi4wODQsMTAuNTcxLTI1Ljk4MUMzNjIuNTk3LDIxMi4zMjEsMzU5LjA3MSwyMDMuNzU1LDM1Mi4wMjUsMTk2LjcxMnoiLz4KPC9nPgo8L3N2Zz4=') no-repeat top left;
            background-size: contain;
        }

        .material .arrow {
            background: url('data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHdpZHRoPSI0NDQuODE5cHgiIGhlaWdodD0iNDQ0LjgxOXB4IiB2aWV3Qm94PSIwIDAgNDQ0LjgxOSA0NDQuODE5IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA0NDQuODE5IDQ0NC44MTk7IgoJIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxwYXRoIGQ9Ik00MzQuMjUyLDExNC4yMDNsLTIxLjQwOS0yMS40MTZjLTcuNDE5LTcuMDQtMTYuMDg0LTEwLjU2MS0yNS45NzUtMTAuNTYxYy0xMC4wOTUsMC0xOC42NTcsMy41MjEtMjUuNywxMC41NjEKCQlMMjIyLjQxLDIzMS41NDlMODMuNjUzLDkyLjc5MWMtNy4wNDItNy4wNC0xNS42MDYtMTAuNTYxLTI1LjY5Ny0xMC41NjFjLTkuODk2LDAtMTguNTU5LDMuNTIxLTI1Ljk3OSwxMC41NjFsLTIxLjEyOCwyMS40MTYKCQlDMy42MTUsMTIxLjQzNiwwLDEzMC4wOTksMCwxNDAuMTg4YzAsMTAuMjc3LDMuNjE5LDE4Ljg0MiwxMC44NDgsMjUuNjkzbDE4NS44NjQsMTg1Ljg2NWM2Ljg1NSw3LjIzLDE1LjQxNiwxMC44NDgsMjUuNjk3LDEwLjg0OAoJCWMxMC4wODgsMCwxOC43NS0zLjYxNywyNS45NzctMTAuODQ4bDE4NS44NjUtMTg1Ljg2NWM3LjA0My03LjA0NCwxMC41NjctMTUuNjA4LDEwLjU2Ny0yNS42OTMKCQlDNDQ0LjgxOSwxMzAuMjg3LDQ0MS4yOTUsMTIxLjYyOSw0MzQuMjUyLDExNC4yMDN6Ii8+CjwvZz4KPC9zdmc+') no-repeat top left;
            background-size: contain;
        }

        .material .download {
            background: url('data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAyOS45NzggMjkuOTc4IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAyOS45NzggMjkuOTc4OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxnPgoJPHBhdGggZD0iTTI1LjQ2MiwxOS4xMDV2Ni44NDhINC41MTV2LTYuODQ4SDAuNDg5djguODYxYzAsMS4xMTEsMC45LDIuMDEyLDIuMDE2LDIuMDEyaDI0Ljk2N2MxLjExNSwwLDIuMDE2LTAuOSwyLjAxNi0yLjAxMgoJCXYtOC44NjFIMjUuNDYyeiIvPgoJPHBhdGggZD0iTTE0LjYyLDE4LjQyNmwtNS43NjQtNi45NjVjMCwwLTAuODc3LTAuODI4LDAuMDc0LTAuODI4czMuMjQ4LDAsMy4yNDgsMHMwLTAuNTU3LDAtMS40MTZjMC0yLjQ0OSwwLTYuOTA2LDAtOC43MjMKCQljMCwwLTAuMTI5LTAuNDk0LDAuNjE1LTAuNDk0YzAuNzUsMCw0LjAzNSwwLDQuNTcyLDBjMC41MzYsMCwwLjUyNCwwLjQxNiwwLjUyNCwwLjQxNmMwLDEuNzYyLDAsNi4zNzMsMCw4Ljc0MgoJCWMwLDAuNzY4LDAsMS4yNjYsMCwxLjI2NnMxLjg0MiwwLDIuOTk4LDBjMS4xNTQsMCwwLjI4NSwwLjg2NywwLjI4NSwwLjg2N3MtNC45MDQsNi41MS01LjU4OCw3LjE5MwoJCUMxNS4wOTIsMTguOTc5LDE0LjYyLDE4LjQyNiwxNC42MiwxOC40MjZ6Ii8+CjwvZz4KPC9zdmc+Cg==') no-repeat top left;
            background-size: contain;
            width: 0.9em;
            height: 0.9em;
            display: inline-block;
            margin-left: 0.3em;
            margin-top: 0.1em;
        }

        .tags {
            font-size: 12px;
            list-style: none;
            margin: 0 0 0 1em;
            padding: 0;
            display: inline-block;
            position: relative;
            top: 0.25em;
        }

        .tags li {
            float: left;
            height: 1.6em;
            margin: 0 1em 0 0;
        }

        .tag {
            font-size: 1em;
            background: #7dacd7;
            border-radius: 0.3em 0 0 0.3em;
            color: #ffffff;
            display: inline-block;
            line-height: 1.6em;
            padding: 0 1em 0 1.5em;
            position: relative;
            margin: 0;
            text-decoration: none;
            -webkit-transition: color 0.2s;
            height: 1.5em;
            overflow: hidden;
        }

        .tag::before {
            background: #ffffff;
            border-radius: 1em;
            box-shadow: inset 0 1px rgba(0, 0, 0, 0.25);
            content: '';
            height: 0.5em;
            left: 0.6em;
            position: absolute;
            width: 0.5em;
            top: 0.6em;
        }

        .tag::after {
            background: white;
            border-bottom: 0.8em solid transparent;
            border-left: 0.8em solid #7dacd7;
            border-top: 0.8em solid transparent;
            height: 0em;
            content: '';
            position: absolute;
            right: 0;
            top: 0;
        }

        .tag:hover {
            background-color: #2c9c3d;
            color: white;
        }

        .tag:hover::after {
            border-left-color: #2c9c3d;
        }
        </style>
        
        <script type='text/javascript'>
            // hasClass
            function hasClass(elem, className) {
                return new RegExp(' ' + className + ' ').test(' ' + elem.className + ' ');
            }
            // toggleClass
            function toggleClass(elem, className) {
                var newClass = ' ' + elem.className.replace( /[\\t\\r\\n]/g, ' ' ) + ' ';
                if (hasClass(elem, className)) {
                    while (newClass.indexOf(' ' + className + ' ') >= 0 ) {
                        newClass = newClass.replace( ' ' + className + ' ' , ' ' );
                    }
                    elem.className = newClass.replace(/^\\s+|\\s+$/g, '');
                } else {
                    elem.className += ' ' + className;
                }
            }
        </script>
        </head>";

		$this->writeToFile($headHtml, $outputFile);
	}

	public function writeToFile($content, $outputFile) {
		file_put_contents($outputFile, $content, FILE_APPEND);
	}

	/**
	 * Make shure that file and folder Stucture of $outputFile exists
	 *
	 * @param string $outputFile
	 * @param bool   $alsoDeleteFile
	 */
	public function touchFile($outputFile, $alsoDeleteFile = TRUE) {
		if (file_exists($outputFile)) {
			if ($alsoDeleteFile === TRUE) {
				unlink($outputFile);
			}

		} else if (!file_exists(dirname($outputFile))) {
			mkdir(dirname($outputFile), 0777, TRUE);
		}

		touch($outputFile);
	}

	public function startHtml($filePath) {
		$this->writeToFile('<!DOCTYPE html><html>', $filePath);
	}

	public function endHtml($filePath) {
		$this->writeToFile('</html>', $filePath);
	}

	public function startBody($filePath) {
		$this->writeToFile('<body>', $filePath);
	}

	public function endBody($filePath) {
		$this->writeToFile('</body>', $filePath);
	}

	protected function getHtmlIdFromString($string) {
		$string = preg_replace('~[ \._-]~', '', $string);

		// src: http://stackoverflow.com/a/158247
		$string = strtr(utf8_decode($string),
						utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
						'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');

		return $string;
	}

	protected function getRelativePath($from, $to) {
		// some compatibility fixes for Windows paths
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$to   = is_dir($to) ? rtrim($to, '\/') . '/' : $to;
		$from = str_replace('\\', '/', $from);
		$to   = str_replace('\\', '/', $to);

		$from    = explode('/', $from);
		$to      = explode('/', $to);
		$relPath = $to;

		foreach ($from as $depth => $dir) {
			// find first non-matching dir
			if ($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if ($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath   = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}

		return implode('/', $relPath);
	}


}