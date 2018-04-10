<script>
    $(document).ready(function() {

        var element_page = $('.typecho-pager');
        var element_show = $('#theme-tbody');
        var start = "https://lichaoxi.com/shop/public/theme?page=<?php _e($request->get('page', 1))?>";
        var path = "<?php $options->adminUrl('extending.php?panel=Shop%2Fshop.php&page=');?>"

        function getThemes(element_page, url) {
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
            })
            .done(function(data) {
                if(data.error != undefined) {
                    alert(data.error);
                    return;
                }
                element_page.html('');
                page(element_page, data);
                show(element_show, data);
            })
            .fail(function() {
                alert('获取数据失败');
            })
            .always(function() {
                // console.log("complete");
            });
        }

        function page(element_page, data) {
            if(data.current_page != 1) {
                element_page.append('<li class="prev"><a href="' + path + (data.current_page - 1) +'">&laquo;</a></li>');
            }

            for(var i = -2; i <= 2; i++) {
                if(i == 0)
                    element_page.append('<li class="current"><a href="' + path + (i + data.current_page) +'">'+ (i + data.current_page) +'</a></li>');
                else if(i + data.current_page > 0 && i + data.current_page <= data.last_page)
                    element_page.append('<li ><a href="' + path + (i + data.current_page) +'">'+ (i + data.current_page) +'</a></li>');
            }

            if(data.current_page != data.last_page) {
                element_page.append('<li class="next"><a href="' + path + (data.current_page + 1) +'">&raquo;</a></li>');
            }
        }

        function show(element_show, data) {
            var js = new Array(<?php Typecho_Widget::widget('Widget_Themes_List')->to($themes);
                    while($themes->next()) { echo '\'', $themes->name, '\', '; }
                ?>);
            var now_theme = '<?php $options->theme(); ?>';

            $.each(data.data, function(key, value) {
                element_show.append('<tr id="theme-'+ value.name +'">\
                    <td valign="top"><img src="'+ value.screen +'" height="210px"/></td>\
                    <td valign="top">\
                        <h3>'+ value.title +'</h3>\
                        <cite> 作者: <a href="' + value.homepage + '">' + (value.author == null ? '未知' : value.author) + '</a>&nbsp;&nbsp; 版本: ' + (value.version == null ? '未知' : value.version) +'</cite>\
                        <p>'+ value.description +' </p>\
                        <p id="p-p-'+ value.name +'" class="hidden">下载进度：<span id="progressbar-span-'+ value.name +'" class="progressbar-span">0%</span></p>\
                        <p><div class="progress" id="progress-'+ value.name +'"><div class="progressbar" id="progressbar-'+ value.name +'">&nbsp;</div></div></p>\
                        <p id="p-a-'+ value.name +'"><a class="preview" href="' + value.show + '">预览</a> &nbsp;\
                        <a class="download" href="javascript:downloadFile(\''+value.download+'\', \''+value.name+'\')">加载</a>\
                        </p>\
                    </td></tr>');
            });

            $.each(js, function(index, el) {
                if(el != now_theme) $('#p-a-'+el.replace(' ', '-space-')).html('<b>已下载</b> &nbsp; <a>移除</a>');
                else  $('#p-a-'+el).html('<b>已下载</b> &nbsp; <b>当前主题无法移除</b>');
                $('#p-p-'+el).remove();
            });
        }

        window.downloadFile = function(url, name) {

            var tmp_path;
            var file_size;
            var flag = false;
            var url_action = '<?php $options->index('/action/shop-plugin');?>';

            $.ajax({
                url: url_action + '?do=preparedownload&url='+url+'&name='+name,
                type: 'GET',
                dataType: 'json',
                beforeSend: prepareDownload(name)
            }).done(function(data) {
                if(data.error != undefined) {
                    alert(data.error);
                    errorStopDownload(name);
                    return;
                }

                file_size = data.file_size;
                tmp_path = data.tmp_path;

                $.ajax({
                    url: url_action + '?do=startdownload&tmp_path='+data.tmp_path+'&url='+url,
                    type: 'GET',
                    dataType: 'json'
                }).done(function(json) {
                    startZip(name)
                    flag = true;

                    $.ajax({
                        url: url_action + '?do=zip&tmp_path='+data.tmp_path+'&name='+name,
                        type: 'GET',
                        dataType: 'json',
                    })
                    .done(function(data) {
                        if(data.error != undefined) {
                            alert(data.error);
                            errorStopDownload(name);
                            return;
                        }
                        finishZip(name);
                        stopDownload(name);
                    });
                });

                startDownload(name);

                var tmp_size = 0;
                var interval_id = window.setInterval(function() {
                    if (tmp_size >= file_size && !flag) {
                        finishDownload(name);
                        clearInterval(interval_id);
                    } else {
                        $.ajax({
                            url:  url_action + '?do=getfilesize&tmp_path='+data.tmp_path,
                            type: 'GET',
                            dataType: 'json',
                        })
                        .done(function(json2) {
                            tmp_size = json2.size;
                            if(!flag) {
                                if(tmp_size == -1) {
                                    //finishDownload(name)
                                    clearInterval(interval_id);
                                } else {
                                    var fixed_size = (json2.size / file_size * 100).toFixed(2)
                                    $('#progressbar-'+name).css('width', fixed_size + '%');
                                    $('#progressbar-span-'+name).html(fixed_size + '%');
                                }
                            } else {
                                finishDownload(name)
                                clearInterval(interval_id);
                            }
                        });
                    }
                }, 300);
            });
        }

        getThemes(element_page, start);

        function prepareDownload(name) {
            $('#progress-'+name).show('normal');
            $('#progressbar-span-'+name).parent().show('normal');
            $('#progressbar-span-'+name).html('正在准备下载...');
        }

        function errorStopDownload(name) {
            $('#progress-'+name).hide('normal');
            $('#progressbar-span-'+name).parent().hide('normal');
        }


        function stopDownload(name) {
            $('#progress-'+name).hide('normal');
            $('#progressbar-span-'+name).parent().hide('normal');
            $('#p-a-'+name.replace(' ', '-space-')).html('<b>已下载</b> &nbsp; <a>移除</a>');
        }

        function startDownload(name) {
            $('#progressbar-span-'+name).html('正在计算文件大小');
        }

        function startZip(name) {
            $('#progressbar-'+name).css('width', '100%');
            $('#progressbar-span-'+name).html('正在解压文件');
        }

        function finishZip(name) {
            $('#progressbar-span-'+name).html('文件解压完成');
        }

        function finishDownload(name) {
            $('#progressbar-span-'+name).html('下载完成');
            $('#progressbar-'+name).css('width', '100%');
        }

    })
</script>