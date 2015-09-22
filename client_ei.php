<?php $privileges = $this->template->get_data('privileges'); ?>
<?php $data = $this->template->get_data('client_history'); ?>

<?php get_header(); ?>
    <?php get_sidebar(); ?>
    
    <section id="content_container">
        <article id="content">
            <header class="header">
                <h1>
                    <?php echo get_pagename(); ?>
                    <?php if (in_array('client_history_add', $privileges['privileges'])) { ?>
                        <!--<a href="#ajax_client_history_add" id="client_history_add" data-size="500px" data-title="<?php echo lang('add_client_history'); ?>" class="modal btn btn_blue add_new"><?php echo lang('add'); ?></a>-->

                        <a href="#" id="client_history_add" data-size="500px"  class="modal btn btn_blue add_new"><?php echo lang('add'); ?></a>
                    <?php } ?>
                </h1>
            </header>

            <div class="block">
                <table class="client_history_table light_table" style="margin: -5px 0px;">
                    <tr>
                        <!--<th style="width: 130px;"><?php //echo lang('date'); ?></th>-->
                        <th><?php echo lang('product'); ?></th>
                        <th style="width: 90px; text-align: center;"><i class="fa fa-bars"></i></th>
                    </tr>
                    <?php if (is($data['list'])) { ?>
                        <?php foreach ($data['list'] as $item) { ?>
                            <tr title="ID: <?php echo $item['id']; ?>">
                                <!--<td style="width: 130px;">
                                    <?php //echo $item['date']; ?><br />
                                    <small><?php //echo $item['expert_name']; ?></small>
                                </td>-->
                                <td><?php echo $item['item_caption']; ?></td>
                                <td style="width: 90px; text-align: center;" class="actions">
                                    <!--<a href="#ajax_client_history_edit" id="client_history_edit" data-size="600px" data-id="<?php //echo $item['id']; ?>" data-title="Редактировать историю клиента" class="action_edit modal btn btn_small btn_blue"><i class="fa fa-pencil"></i></a>
                                    <a href="#" data-id="<?php //echo $item['id']; ?>" class="action_remove btn btn_small btn_red" style="margin-left: 10px;"><i class="fa fa-trash"></i></a>-->

                                    <a href="#" id="client_history_edit" data-size="600px"  class="action_edit modal btn btn_small btn_blue"><i class="fa fa-pencil"></i></a>
                                    <a href="#"  class="action_remove btn btn_small btn_red" style="margin-left: 10px;"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="100" class="empty">
                                <h2><?php echo lang('nothing_added'); ?></h2>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </article>
    </section>
    
    <?php ob_start(); ?>
        <script type="text/javascript">
            $(document).ready(function() {
                $("#client_history_add").unbind("click.client_history_add").bind("click.client_history_add", function() {
                    var $this = $(this);

                    $.ajax({
                        type: "POST",
                        url: "<?php echo get_info('site_url'); ?>/client_history_modals", 
                        data: {
                            id: <?php echo $data['client_info']['id']; ?>,
                            action: "add"
                        },
                        cache: false,
                        success: function(data) {
                            $($this.attr("href")).find(".modal_inner").empty().html(data);
                        },
                        error: function(data) {
                            alert("<?php echo lang('ajax_error'); ?>");
                        },
                    });
                });
                
                $(".client_history_table .actions .action_edit").unbind("click.client_history_edit").bind("click.client_history_edit", function() {
                    var $this = $(this);
    
                    $.ajax({
                        type: "POST",
                        url: "<?php echo get_info('site_url'); ?>/client_history_modals", 
                        data: {
                            id: <?php echo $data['client_info']['id']; ?>,
                            item_id: $this.attr("data-id"),
                            action: "edit"
                        },
                        success: function(data) {
                            $($this.attr("href")).find(".modal_inner").empty().html(data);
                        },
                        error: function() {
                            alert("<?php echo lang('ajax_error'); ?>");
                        },
                    });
                    
                    return false;
                });
                
                $(".client_history_table .actions .action_remove").unbind("click.client_history_remove").bind("click.client_history_remove", function() {
                    if (! confirm("Вы действительно хотите удалить эту запись?")) {
                        return false;
                    } else {
                        var $this = $(this);
        
                        $.ajax({
                            type: "POST",
                            url: "<?php echo get_info('site_url'); ?>/client_history_remove_action", 
                            data: {
                                id: <?php echo $data['client_info']['id']; ?>,
                                item_id: $this.attr("data-id"),
                            },
                            success: function(data) {
                                $("#notify_container").find(".notify_message").remove();
        
                                try {
                                    var obj = $.parseJSON(data);
                                     
                                    if (obj.redirect) {
                                        window.location.href = "" + obj.redirect + "";
                                    }
                                    
                                    return true;
                                } catch (e) {
                                    $("#ajax_notifies").empty().html(data);
                                    
                                    return false;
                                }
                            },
                            error: function() {
                                alert("<?php echo lang('ajax_error'); ?>");
                            },
                        });
                        
                        return false;
                    }
                });
            });
        </script>
    <?php
        $output = ob_get_clean();
        set_scripts($output);
    ?>
    
<?php get_footer(); ?>