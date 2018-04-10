<?php
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php';?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs fix-tabs clearfix">
                    <li class="current"><a href="<?php $options->adminUrl('themes.php');?>"><?php _e('主题商店');?></a></li>
                    <li><a href="<?php $options->adminUrl('themes.php');?>"><?php _e('我的主题');?></a></li>
                </ul>

                <div class="typecho-table-wrap">
                    <table class="typecho-list-table typecho-theme-list">
                        <colgroup>
                            <col width="35%" />
                            <col />
                        </colgroup>

                        <thead>
                            <th>截图</th>
                            <th>详情</th>
                        </thead>

                        <tbody id="theme-tbody">
                        </tbody>
                    </table>
                </div>
                <div class="typecho-list-operate clearfix">
                    <ul class="typecho-pager">
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<style>
    .progress {
        width: 100%;
        border: solid 1px gray;
        height: 20px;
        display: none;
    }
    .progressbar {
        width: 0%;
        transition:width 0.33s;
        background-color: gray;
        text-align: center;
    }
    .progressbar-span {
        font-weight: bold;
    }
</style>


<?php
include 'shop-js.php';
include 'footer.php';
?>
