/*
 *#################################################################################################################################
 *
 *    后台请求接口
 *    此文件依赖  jquery.js      | share authorization 方法 依赖 wx.bate.js |
 *
 *#################################################################################################################################
 */
/*ajax 请求统一处理*/
$.ajaxSetup({
    contentType: "application/x-www-form-urlencoded;charset=utf-8",
    /*请求前*/
    beforeSend: function () {

        // 这里加上loading 可根据需求自定义
        $.MyDialog.loading('正在处理');
    },
    /*请求完成*/
    complete: function (XMLHttpRequest, textStatus) {

        // 这里取消loading 可根据需求自定义
        $.MyDialog.destroy();
    },
    /*错误处理*/
    error: function (xhr, status, error) {
        switch (xhr.status) {
            // 验证出错
            case 422:
                //提交字段验证出错  错误信息为  xhr.responseJSON.error
                //处理错误信息
                $.MyDialog.error(xhr.responseJSON.error);
                break;
            case 500:
                //提交字段验证出错  错误信息为  xhr.responseJSON.error
                //处理错误信息
                $.MyDialog.error('服务器繁忙，请稍后再试!');
                break;
            default:
                //这里统一处理上面没有捕获的错误   可根据需求自定义
                $.MyDialog.error('服务器繁忙，请稍后再试!~~~');
                break;
        }
    }
});

var api = {
    /**
     *  是否开启调试模式
     */
    debug: true,

    /*
    * 分享页面 url
    * */
    shareUrl: '',
    /*
    * 路由地址对象
    * */
    urlObj: null,
    /*
    *  根路径
    * */
    baseUrl: 'https://wx.sanshanwenhua.com/vlvl/api/sswh',

    // **********************
    // H5
    /**
     *   获取/记录 用户信息
     */
    user: function () {
        return new Promise(function (resolve, reject) {
            $.get(api.baseUrl + '/x200817/site2_1/user',
                function (res) {
                    api.debug && console.log(res);
                    resolve(res);
                });
        });
    },

    /**
     * 提交信息
     */
    post: function (data) {
        return new Promise(function (resolve, reject) {
            $.post(api.baseUrl + '/x200817/site2_1/post',
                /*接收参数-start*/
                {
                    name: data.name,      //姓名
                    phone: data.phone,    //电话
                    room_no: data.room_no //房号
                },
                function (res) {
                    api.debug && console.log(res);
                    resolve(res);
                });
        });
    },
    //***************************

    //***************************
    // 大屏幕
    /**
     *   获取抽奖用户池
     */
    prizeUser: function () {
        return new Promise(function (resolve, reject) {
            $.get(api.baseUrl + '/x200817/site2_1/prize_user',
                function (res) {
                    api.debug && console.log(res);
                    resolve(res);
                });
        });
    },

    /**
     *
     * 获取当前抽奖轮数
     */
    round: function () {
        return new Promise(function (resolve, reject) {
            $.get(api.baseUrl + '/x200817/site2_1/get_round',
                function (res) {
                    api.debug && console.log(res);
                    resolve(res);
                });
        });
    },
    /**
     * 抽奖
     */
    prize: function () {
        return new Promise(function (resolve, reject) {
            $.post(api.baseUrl + '/x200817/site2_1/prize',
                function (res) {
                    api.debug && console.log(res);
                    resolve(res);
                });
        });
    },
    //********************
};