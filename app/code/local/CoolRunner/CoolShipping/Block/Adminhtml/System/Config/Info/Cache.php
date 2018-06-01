<?php

class CoolRunner_CoolShipping_Block_Adminhtml_System_Config_Info_Cache
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface {

    public function render(Varien_Data_Form_Element_Abstract $element) {
        /** @var CoolRunner_CoolShipping_Model_Order_Pdf $pdf */
        $pdf = Mage::getModel('coolrunner/order_pdf');
        $helper = Mage::helper('coolrunner');

        ob_start();


        $cache_type = Mage::helper('coolrunner')->getConfig('coolrunner/settings/cache', Mage_Core_Model_App::ADMIN_STORE_ID);

        if ((string)$cache_type !== '0') {

            if (isset($_GET['clear-cache'])) {
                $list = explode(',', $_GET['clear-cache']);
                if (!empty($list)) {
                    $pdf->clearCacheTypes($list);
                }
            }

            $sizes = $pdf->getCacheSize();

            ?>
            <tr>
                <td class="value">
                    <div class="grid">
                        <div class="grid">
                            <table class="border" cellpadding="0" cellspacing="0">
                                <tbody>
                                <tr class="headings">
                                    <th><?php echo $helper->__('Type') ?></th>
                                    <th style="text-align:right;"><?php echo $helper->__('Size') ?></th>
                                    <th></th>
                                </tr>
                                <?php foreach ($sizes as $key => $size) : ?>
                                    <tr class="headings">
                                        <th><?php echo ucfirst($key) ?></th>
                                        <td style="text-align:right;"><?php echo $size ?></td>
                                        <td style="width: 1px;">
                                            <a link="?clear-cache=<?php echo $key ?>" class="cache-clear">
                                                <button type="button" class="scalable delete" style="display: block; width: 100%;">
                                            <span>
                                                <?php echo sprintf($helper->__('Clear %s cache'), $key) ?>
                                            </span>
                                                </button>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
            <script>
                (function () {
                    var clear_btns = document.querySelectorAll('.cache-clear'),
                        xhttp = new XMLHttpRequest();
                    for (var i = 0; i < clear_btns.length; i++) {
                        (function (btn) {
                            btn.addEventListener('click', function (e) {
                                e.preventDefault();
                                var href = this.attributes.link.nodeValue;
                                xhttp.addEventListener('load', function () {
                                    location.reload();
                                });
                                xhttp.open('GET', href, true);

                                xhttp.send();
                                return false;
                            }, true);
                        })(clear_btns[i])
                    }
                })();
            </script>
            <?php
        } else {
            ?>
            <tr>
                <td class="value"><b>Caching is disabled.</b></td>
            </tr>
            <tr>
                <td class="value">Enable caching in the main Configuration of CoolShipping by setting Cache Type to any value but "None".</td>
            </tr>
            <?php
        }

        return ob_get_clean();
    }

}
