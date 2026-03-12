<?php

if (!defined('ABSPATH')) {
    exit;
}

class CPC_Projects_Recent_Tasks_Widget extends WP_Widget {
    private $default_task_number = 5;

    public function __construct() {
        parent::__construct(
            'cpc_projects_recent_tasks',
            __('(Projects) Meine letzten Tasks', CPC2_TEXT_DOMAIN),
            array(
                'classname' => 'cpc_projects_recent_tasks_widget',
                'description' => __('Zeigt die letzten Tasks des aktuell angemeldeten Nutzers.', CPC2_TEXT_DOMAIN),
            )
        );

        $this->register_sidebar();
    }

    public static function register_widget() {
        register_widget('CPC_Projects_Recent_Tasks_Widget');
    }

    private function register_sidebar() {
        register_sidebar(array(
            'name' => __('Projects', CPC2_TEXT_DOMAIN),
            'id' => 'cpc-projects',
            'description' => __('Sidebar fuer Projects-bezogene Widgets.', CPC2_TEXT_DOMAIN),
            'before_widget' => '<aside id="%1$s" class="sidebar-widgets widget %2$s">',
            'after_widget' => '</aside>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3><div class="widget-clear"></div>',
        ));
    }

    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? (string)$instance['title'] : __('Meine letzten Tasks', CPC2_TEXT_DOMAIN);
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

        $tasks = cpc_projects_get_user_recent_tasks(get_current_user_id(), $task_number);
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

            $url = $project_url.'#cpc-project-task-'.(int)$task->id;
            $label = cpc_projects_render_task_priority_label((int)$task->priority);
            $time_label = '';
            if (!empty($task->date_added)) {
                $time_label = sprintf(
                    __('hinzugefuegt vor %s', CPC2_TEXT_DOMAIN),
                    human_time_diff(strtotime((string)$task->date_added), current_time('timestamp', 1))
                );
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
        $title = !empty($instance['title']) ? (string)$instance['title'] : __('Meine letzten Tasks', CPC2_TEXT_DOMAIN);
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
