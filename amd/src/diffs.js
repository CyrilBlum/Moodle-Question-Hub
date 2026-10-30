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

/**
 * Line diffs: expand or collapse all files and reveal unchanged lines.
 *
 * @module     qbank_questionhub/diffs
 * @copyright  2026 Cyril Blum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

let initialised = false;

/**
 * Opens or closes the files of a diff list.
 *
 * @param {Element|Document} root Element holding the files.
 * @param {boolean} open Whether to open them.
 */
const toggleFiles = (root, open) => {
    root.querySelectorAll('details.qh-file').forEach((file) => {
        file.open = open;
    });
};

/**
 * Listens for clicks on the diff controls, wherever the template rendered them.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;
    document.addEventListener('click', (event) => {
        const list = event.target.closest('[data-qh-diffs]');
        const all = event.target.closest('[data-qh-diff-all]');
        if (list && all) {
            toggleFiles(list, all.getAttribute('data-qh-diff-all') === 'open');
            return;
        }
        const gap = event.target.closest('[data-qh-gap]');
        if (list && gap) {
            list.querySelectorAll(`[data-qh-gap-row="${gap.getAttribute('data-qh-gap')}"]`).forEach((row) => {
                row.classList.remove('qh-hidden');
            });
            gap.closest('tr').remove();
            return;
        }
        const review = event.target.closest('[data-qh-review-all]');
        if (review) {
            const root = document.querySelector('.qbank_questionhub_review');
            toggleFiles(root, review.getAttribute('data-qh-review-all') === 'open');
        }
    });
};
