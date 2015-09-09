<?php

class Service_App extends Service_Base {

    public static function make_delta($app_id)
    {
        // ��ȡ����Ϣ
        $app = Model_App::instance()->get_info_by_id($app_id);
        if (empty($app)) {
            Rest_Log::error("{$app_id}��Ӧ�ļ�¼�����ڣ��޷�����������");
            throw new Rest_Exception(Rest_Response::HTTP_SERVER_ERROR, "{$app_id}��Ӧ�ļ�¼�����ڣ��޷�����������");
        }

        // �жϵ�ǰ���Ƿ�Ϊǿ������
        if (Model_App::instance()->is_force_update($app['force_update'])) {
            Rest_Log::trace("{$app_id}��Ӧ�ļ�¼�����ڣ��޷�����������");
            return;
        }

        // ��ȡ��ǰ��֮ǰ��X����
        $app_delta_config = Rest_Config::get('appdelta');
        $condition = [
            'client_id' => $app['client_id'],
            'platform' => $app['platform'],
            'channel' => $app['channel'],
            'inner_version < ' => $app['inner_version'],
        ];
        $src_app_list = Model_App::instance()->get_list($condition, ['inner_version' => 'desc'], 0, $app_delta_config['limit']);
        if (empty($src_app_list)) {
            Rest_Log::trace("{$app_id}��Ӧ�İ��б����ڣ��Ͳ�������������");
            return;
        }
        foreach ($src_app_list as $src_app) {
            self::do_make_delta($src_app, $app);
        }
    }

    protected static function do_make_delta($src_app, $dst_app) // ִ������������
    {
        Rest_Log::trace("����������");
        Rest_Log::trace("��1{$src_app['name']},�ڲ���{$src_app['inner_version']}");
        Rest_Log::trace("��2{$dst_app['name']},�ڲ���{$dst_app['inner_version']}");

        // �ж��Ƿ�������������
        $delta_app = Model_AppDelta::instance()->get_info_by_condition(['from_app_id' => $src_app['id'], 'to_app_id' => $dst_app['id']]);
        if (!empty($delta_app)) {
            $need_gen = false;

            $delta_resolvepath = Service_File::get_filepath_by_fkey($delta_app['fkey'], $delta_app['name'], Service_File::DST_APP);
            if (file_exists($delta_resolvepath)) {
                Rest_Log::trace("������{$delta_app['name']}�Ѵ��ڣ�����������");
                return;
            }
        } else {
            $need_gen = true;

            $delta_fkey = Helper_Api::generate_key(true);
            $delta_ext = Service_File::get_file_ext($src_app['name']);
            $delta_filename = "{$src_app['version']}_{$dst_app['version']}_delta.{$delta_ext}"; // v1_v2_delta.apk
            $delta_resolvepath = Service_File::get_dir_by_fkey($delta_fkey, Service_File::DST_APP) . '/' . $delta_fkey . '.' . $delta_ext;
        }

        Rest_Log::trace("������{$delta_app['name']}������...");

        // ���ľ���·��
        $src_resolvepath = Service_File::get_filepath_by_fkey($src_app['version_key'], $src_app['name'], Service_File::DST_APP);
        $dst_resolvepath = Service_File::get_filepath_by_fkey($dst_app['version_key'], $dst_app['name'], Service_File::DST_APP);

        Rest_Log::trace("��1�ľ���·����{$src_resolvepath}");
        Rest_Log::trace("��2�ľ���·����{$dst_resolvepath}");
        Rest_Log::trace("�������ľ���·����{$delta_resolvepath}");

        // ��ȡ�����󣬱���������������
        $app_delta_config = Rest_Config::get('appdelta');
        $cmd = "{$app_delta_config['python_bin']} {$app_delta_config['make_delta']} -f \"{$src_resolvepath}\" -s \"{$dst_resolvepath}\" -o \"{$delta_resolvepath}\"";
        Rest_Log::trace("ִ�е�����Ϊ:{$cmd}");
        Rest_Log::trace('��ʼ����������' . time());
        exec($cmd);
        Rest_Log::trace('��������������' . time());

        // ������¼
        if ($need_gen) {
            Rest_Log::trace('������������¼');
            $props = [
                'from_app_id' => $src_app['id'],
                'to_app_id' => $dst_app['id'],
                'md5' => md5_file($delta_resolvepath),
                'size' => filesize($delta_resolvepath),
                'fkey' => $delta_fkey,
                'name' => $delta_filename,
                'mime' => $src_app['mime'],
                'create_time' => time(),
            ];
            $delta_id = Model_AppDelta::instance()->gen_one($props);
        } else {
            $delta_id = $delta_app['id'];
        }

        return $delta_id;
    }

}