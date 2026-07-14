<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_dixeo\dto;

/**
 * A local filesystem path to upload as one multipart file (e.g. SCORM text extract).
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class file_upload_part {

    /**
     * Create a file upload part DTO.
     *
     * @param string $path Absolute path readable by curl (temp file).
     * @param string $filename Logical filename sent to the API (stable per activity).
     * @param string $mimetype MIME type for the part.
     * @param bool $deleteafterupload If true, the API client unlinks $path after POST.
     */
    public function __construct(
        /** @var string Absolute path readable by curl (temp file). */
        public readonly string $path,
        /** @var string Logical filename sent to the API (stable per activity). */
        public readonly string $filename,
        /** @var string MIME type for the part. */
        public readonly string $mimetype = 'text/plain',
        /** @var bool If true, the API client unlinks $path after POST. */
        public readonly bool $deleteafterupload = true,
    ) {
    }
}
