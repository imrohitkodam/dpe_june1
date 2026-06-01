/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/core
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

if (addEventListener) {
        const xx = document.querySelectorAll("img.jch-lazyload");

        for (var i = 0; i < xx.length; i++) {
                if (xx[i].complete) {
                        addHeight(xx[i]);
                } else {
                        xx[i].addEventListener('load', function (event) {
                                addHeight(event.target);
                        })
                }
        }

        document.addEventListener('lazybeforeunveil', function (e) {
                if (e.target.nodeName == 'IMG') {
                        addHeight(e.target);
                }
        });
}
;

function addHeight(el) {
        var ht = el.getAttribute('height');
        var wt = el.getAttribute('width');

        el.style.height = ht ? ((wt && el.offsetWidth > 40) ? (el.offsetWidth * ht) / wt : ht) + 'px' : 'auto';
};
