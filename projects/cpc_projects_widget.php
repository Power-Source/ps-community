<?php

if (!defined('ABSPATH')) {
    exit;
}

class CPC_Projects_Recent_Tasks_Widget extends WP_Widget {
    private $default_task_number = 5;

    public function __construct() {
        parent::__construct(
            'cpc_projects_recent_tasks',
            __('(Projects) Meine Tasks', CPC2_TEXT_DOMAIN),
            array(
                'classname' => 'cpc_projects_recent_tasks_widget',
                'description' => __('Zeigt die letzten Tasks des aktuell angemeldeten Nutzers.', CPC2_TEXT_DOMAIN),
            )
        );
    }

    public static function register_widget() {
        register_widget('CPC_Projects_Recent_Tasks_Widget');
    }

    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? (string)$instance['title'] : __('Meine Tasks', CPC2_TEXT_DOMAIN);
        $task_number = !empty($instance['task_number']) ? (int)$instance['task_number'] : $this->default_task_number;
        $task_number = max(1, min(20, $task_number));

        echo $args['before_widget'];
        if ($title !== '') {
            echo $args['before_title'].apply_filters('widget_title', $title).$args['after_title'];
        }

        if (!is_user_logged_in()) {
            echo '<div class="cpc_projects_widget_no_tasks">'.esc_html__('Bitte anmelden, um Deine Tasks zu sehen.', CPC2_TEXT_DOMAIN).'</div>';
            echo $args['after_widget'];
            return;
        }

        $tasks = cpc_projects_get_user_open_tasks(get_current_user_id(), $task_number);
        if (empty($tasks)) {
            echo '<div class="cpc_projects_widget_no_tasks">'.esc_html__('Keine Tasks gefunden.', CPC2_TEXT_DOMAIN).'</div>';
            echo $args['after_widget'];
            return;
        }

        echo '<ul class="cpc_projects_widget_recent_tasks">';
        foreach ($tasks as $task) {
            $project_url = get_permalink((int)$task->project_id);
            if (!$project_url) {
                continue;
            }

            $url = function_exists('cpc_projects_get_task_url') ? cpc_projects_get_task_url((int)$task->project_id, (int)$task->id) : $project_url.'#cpc-project-task-'.(int)$task->id;
            $url = add_query_arg('cpc_project_section', 'tasks', $url);
            $label = cpc_projects_render_task_priority_label((int)$task->priority);
            $time_label = '';
            if (!empty($task->deadline)) {
                $deadline_ts = (int)strtotime((string)$task->deadline);
                $now_ts = current_time('timestamp', 1);
                if ($deadline_ts > $now_ts) {
                    $time_label = sprintf(
                        __('Faellig: %s', CPC2_TEXT_DOMAIN),
                        human_time_diff($now_ts, $deadline_ts)
                    );
                } else {
                    $time_label = __('UEBERFAELLIG', CPC2_TEXT_DOMAIN);
                }
            } else {
                $time_label = __('Keine Frist', CPC2_TEXT_DOMAIN);
            }

            echo '<li class="cpc_projects_widget_recent_item">';
            echo '<h5>';
            echo '<span class="cpc_projects_task_priority priority-'.(int)$task->priority.'">'.esc_html($label).'</span> ';
            echo '<a href="'.esc_url($url).'">'.esc_html($task->title).'</a>';
            echo '</h5>';
            if ($time_label !== '') {
                echo '<div class="cpc_projects_widget_recent_date">'.esc_html($time_label).'</div>';
            }
            echo '</li>';
        }
        echo '</ul>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? (string)$instance['title'] : __('Meine Tasks', CPC2_TEXT_DOMAIN);
        $task_number = !empty($instance['task_number']) ? (int)$instance['task_number'] : $this->default_task_number;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Titel:', CPC2_TEXT_DOMAIN); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('task_number')); ?>"><?php esc_html_e('Anzahl Tasks:', CPC2_TEXT_DOMAIN); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('task_number')); ?>" name="<?php echo esc_attr($this->get_field_name('task_number')); ?>" type="number" min="1" max="20" value="<?php echo esc_attr($task_number); ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['task_number'] = !empty($new_instance['task_number']) ? max(1, min(20, (int)$new_instance['task_number'])) : $this->default_task_number;
        return $instance;
    }
}

add_action('widgets_init', array('CPC_Projects_Recent_Tasks_Widget', 'register_widget'), 20);
