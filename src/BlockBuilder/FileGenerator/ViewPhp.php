<?php namespace BlockBuilder\FileGenerator;

use Concrete\Core\File\Service\File as FileService;
use BlockBuilder\Utility as BlockBuilderUtility;

defined('C5_EXECUTE') or die('Access Denied.');

class ViewPhp
{

    public function generate($postDataSummary, $postData) {

        $filename = 'view.php';

        $code = '';
        $code .= '<?php defined(\'C5_EXECUTE\') or die(\'Access Denied.\'); ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

        if ( ! empty($postData['basic'])) {

            foreach ($postData['basic'] as $k => $v) {

                if ($v['fieldType']=='text_field') {

                    $code .= '<?php if (!empty($'.$v['handle'].')): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<?php echo h($'.$v['handle'].'); ?>'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='textarea') {

                    $code .= '<?php if (!empty($'.$v['handle'].')): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<?php echo nl2br(h($'.$v['handle'].')); ?>'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='wysiwyg_editor') {

                    $code .= '<?php if (!empty($'.$v['handle'].')): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<?php echo $'.$v['handle'].'; ?>'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='select_field') {

                    $code .= '<?php if (!empty($'.$v['handle'].')): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'Key: <?php echo $'.$v['handle'].'; ?><br/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(1).'Value: <?php echo h($'.$v['handle'].'_options[$'.$v['handle'].']); ?>'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='link_from_sitemap') {

                    $code .= '<?php if (!empty($'.$v['handle'].'_link)): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<a href="<?php echo $'.$v['handle'].'_link; ?>';
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= '<?php echo $'.$v['handle'].'_ending; ?>';
                    }
                    $code .= '"';
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= ' title="<?php echo h($' . $v['handle'] . '_title); ?>"';
                    }
                    $code .= '>';
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $code .= PHP_EOL.BlockBuilderUtility::tab(2).'<?php echo h($'.$v['handle'].'_text); ?>'.PHP_EOL;
                        $code .= BlockBuilderUtility::tab(1);
                    }
                    $code .= '</a>'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='link_from_file_manager') {

                    $code .= '<?php if (!empty($'.$v['handle'].'_link)): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<a href="<?php echo $'.$v['handle'].'_link; ?>"';
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= ' title="<?php echo h($' . $v['handle'] . '_title); ?>"';
                    }
                    $code .= '>';
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= PHP_EOL.BlockBuilderUtility::tab(2) . '<?php echo h($' . $v['handle'] . '_text); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(1);
                    }
                    $code .= '</a>'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='external_link') {

                    $code .= '<?php if (!empty($'.$v['handle'].')): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<a href="<?php if ($'.$v['handle'].'_protocol!=\'other\'): ?><?php echo $'.$v['handle'].'_protocol; ?><?php endif; ?><?php echo $'.$v['handle'].'; ?>"';
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $code .= ' title="<?php echo h($' . $v['handle'] . '_title); ?>"';
                    }
                    $code .= '>';
                    if (!empty($v['externalLinkShowTextField'])) {
                        $code .= PHP_EOL.BlockBuilderUtility::tab(2) . '<?php echo h($' . $v['handle'] . '_text); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(1);
                    }
                    $code .= '</a>'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='image') {

                    $code .= '<?php if (!empty($'.$v['handle'].'_link)): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<?php // Original image ?>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(1).'<img src="<?php echo $'.$v['handle'].'_link; ?>" alt="<?php echo h($'.$v['handle'].'_alt); ?>" width="<?php echo $'.$v['handle'].'_width; ?>" height="<?php echo $'.$v['handle'].'_height; ?>" />'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL;

                    if ( ! empty($v['imageCreateFullscreenImage'])) {
                        $code .= '<?php if (!empty($' . $v['handle'] . '_fullscreenLink)): ?>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(1) . '<?php // Fullscreen image ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(1) . '<img src="<?php echo $' . $v['handle'] . '_fullscreenLink; ?>" alt="<?php echo h($' . $v['handle'] . '_alt); ?>" width="<?php echo $' . $v['handle'] . '_fullscreenWidth; ?>" height="<?php echo $' . $v['handle'] . '_fullscreenHeight; ?>" />' . PHP_EOL;

                        $code .= '<?php endif; ?>' . PHP_EOL . PHP_EOL;
                    }

                    if ( ! empty($v['imageCreateThumbnailImage'])) {
                        $code .= '<?php if (!empty($' . $v['handle'] . '_thumbnailLink)): ?>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(1) . '<?php // Thumbnail image ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(1) . '<img src="<?php echo $' . $v['handle'] . '_thumbnailLink; ?>" alt="<?php echo h($' . $v['handle'] . '_alt); ?>" width="<?php echo $' . $v['handle'] . '_thumbnailWidth; ?>" height="<?php echo $' . $v['handle'] . '_thumbnailHeight; ?>" />' . PHP_EOL;

                        $code .= '<?php endif; ?>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= PHP_EOL;

                }

                if ($v['fieldType']=='html_editor') {

                    $code .= '<?php if (!empty($'.$v['handle'].')): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<?php echo $'.$v['handle'].'; ?>'.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='date_picker') {

                    $code .= '<?php if (!empty($'.$v['handle'].')): ?>'.PHP_EOL.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(1).'<?php echo date(\''.$v['datePickerPattern'].'\', strtotime($'.$v['handle'].')); ?>'.PHP_EOL.PHP_EOL;

                    $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL;

                }

            }

        }

        if ( ! empty($postData['entries'])) {

            $code .= PHP_EOL.'<?php // Repeatable entries ?>'.PHP_EOL.PHP_EOL;

            $code .= '<?php if (count($entries)): ?>'.PHP_EOL.PHP_EOL;

            $code .= BlockBuilderUtility::tab(1).'<?php foreach ($entries as $entry): ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

            foreach ($postData['entries'] as $k => $v) {

                if ($v['fieldType']=='text_field') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<?php echo h($entry[\''.$v['handle'].'\']); ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='textarea') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<?php echo nl2br(h($entry[\''.$v['handle'].'\'])); ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='wysiwyg_editor') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<?php echo $entry[\''.$v['handle'].'\']; ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='select_field') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'Key: <?php echo $entry[\''.$v['handle'].'\']; ?><br/>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'Value: <?php echo h($entry_'.$v['handle'].'_options[$entry[\''.$v['handle'].'\']]); ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='link_from_sitemap') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'_link\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<a href="<?php echo $entry[\''.$v['handle'].'_link\']; ?>';
                    if (!empty($v['linkFromSitemapShowEndingField'])) {
                        $code .= '<?php echo $entry[\'' . $v['handle'] . '_ending\']; ?>';
                    }
                    $code .= '"';
                    if (!empty($v['linkFromSitemapShowTitleField'])) {
                        $code .= ' title="<?php echo h($entry[\'' . $v['handle'] . '_title\']); ?>"';
                    }
                    $code .= '>';
                    if (!empty($v['linkFromSitemapShowTextField'])) {
                        $code .= PHP_EOL . BlockBuilderUtility::tab(4) . '<?php echo h($entry[\'' . $v['handle'] . '_text\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3);
                    }
                    $code .= '</a>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='link_from_file_manager') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'_link\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<a href="<?php echo $entry[\''.$v['handle'].'_link\']; ?>"';
                    if (!empty($v['linkFromFileManagerShowTitleField'])) {
                        $code .= ' title="<?php echo h($entry[\'' . $v['handle'] . '_title\']); ?>"';
                    }
                    $code .= '>';
                    if (!empty($v['linkFromFileManagerShowTextField'])) {
                        $code .= PHP_EOL . BlockBuilderUtility::tab(4) . '<?php echo h($entry[\'' . $v['handle'] . '_text\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3);
                    }
                    $code .= '</a>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='external_link') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<a href="<?php if ($entry[\''.$v['handle'].'_protocol\']!=\'other\'): ?><?php echo $entry[\''.$v['handle'].'_protocol\']; ?><?php endif; ?><?php echo $entry[\''.$v['handle'].'\']; ?>"';
                    if (!empty($v['externalLinkShowTitleField'])) {
                        $code .= ' title="<?php echo h($entry[\'' . $v['handle'] . '_title\']); ?>"';
                    }
                    $code .= '>';
                    if (!empty($v['externalLinkShowTextField'])) {
                        $code .= PHP_EOL . BlockBuilderUtility::tab(4) . '<?php echo h($entry[\'' . $v['handle'] . '_text\']); ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3);
                    }
                    $code .= '</a>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='image') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'_link\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<?php // Original image ?>'.PHP_EOL;
                    $code .= BlockBuilderUtility::tab(3).'<img src="<?php echo $entry[\''.$v['handle'].'_link\']; ?>" alt="<?php echo h($entry[\''.$v['handle'].'_alt\']); ?>" width="<?php echo $entry[\''.$v['handle'].'_width\']; ?>" height="<?php echo $entry[\''.$v['handle'].'_height\']; ?>" />'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL;

                    if ( ! empty($v['imageCreateFullscreenImage'])) {
                        $code .= BlockBuilderUtility::tab(2) . '<?php if (!empty($entry[\'' . $v['handle'] . '_fullscreenLink\'])): ?>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(3) . '<?php // Fullscreen image ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3) . '<img src="<?php echo $entry[\'' . $v['handle'] . '_fullscreenLink\']; ?>" alt="<?php echo h($entry[\'' . $v['handle'] . '_alt\']); ?>" width="<?php echo $entry[\'' . $v['handle'] . '_fullscreenWidth\']; ?>" height="<?php echo $entry[\'' . $v['handle'] . '_fullscreenHeight\']; ?>" />' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(2) . '<?php endif; ?>' . PHP_EOL . PHP_EOL;
                    }

                    if ( ! empty($v['imageCreateThumbnailImage'])) {
                        $code .= BlockBuilderUtility::tab(2) . '<?php if (!empty($entry[\'' . $v['handle'] . '_thumbnailLink\'])): ?>' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(3) . '<?php // Thumbnail image ?>' . PHP_EOL;
                        $code .= BlockBuilderUtility::tab(3) . '<img src="<?php echo $entry[\'' . $v['handle'] . '_thumbnailLink\']; ?>" alt="<?php echo h($entry[\'' . $v['handle'] . '_alt\']); ?>" width="<?php echo $entry[\'' . $v['handle'] . '_thumbnailWidth\']; ?>" height="<?php echo $entry[\'' . $v['handle'] . '_thumbnailHeight\']; ?>" />' . PHP_EOL;

                        $code .= BlockBuilderUtility::tab(2) . '<?php endif; ?>' . PHP_EOL . PHP_EOL;
                    }

                    $code .= PHP_EOL;

                }

                if ($v['fieldType']=='html_editor') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'\'])): ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<?php echo $entry[\''.$v['handle'].'\']; ?>'.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL.PHP_EOL;

                }

                if ($v['fieldType']=='date_picker') {

                    $code .= BlockBuilderUtility::tab(2).'<?php if (!empty($entry[\''.$v['handle'].'\'])): ?>'.PHP_EOL.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(3).'<?php echo date(\''.$v['datePickerPattern'].'\', strtotime($entry[\''.$v['handle'].'\'])); ?>'.PHP_EOL.PHP_EOL;

                    $code .= BlockBuilderUtility::tab(2).'<?php endif; ?>'.PHP_EOL.PHP_EOL;

                }

            }

            $code .= BlockBuilderUtility::tab(1).'<?php endforeach; ?>'.PHP_EOL.PHP_EOL;

            $code .= '<?php endif; ?>'.PHP_EOL.PHP_EOL;

        }

        $fileService = new FileService();
        $fileService->append($postDataSummary['blockPath'] . DIRECTORY_SEPARATOR . $filename, $code);

    }

}
