import Vue from 'vue';
import Router from 'vue-router';
import { getLocalStorage } from '@/utils/local-storage';

import home from './home';
import items from './items';
import user from './user';
import order from './order';
import login from './login';
import store from '../store/index';

Vue.use(Router);

const RouterModel = new Router({
  routes: [...home, ...items, ...user, ...order, ...login]
});

// 路由重定向错误通过catch给捕获到
const originalPush = Router.prototype.push
Router.prototype.push = function push(location, onResolve, onReject) {
	if (onResolve || onReject) return originalPush.call(this, location, onResolve, onReject)
	return originalPush.call(this, location).catch(err => err)
}

RouterModel.beforeEach((to, from, next) => {
  const { Authorization } = getLocalStorage(
    'Authorization'
  );
  console.log("Authorization: ", Authorization);
  console.log("meta: ", to.meta);
  console.log("to-path: ", to.path);

  // 需要登录
  if (!Authorization) {
    console.log("meta-login: ", to.meta.login);
    if (to.meta.login) {
      next({ name: 'login', query: { redirect: to.name } });
      return;
    }
  }

  //页面顶部菜单拦截
  let emptyObj = JSON.stringify(to.meta) == "{}";
  let undefinedObj = typeof (to.meta.showHeader) == "undefined";
  if (!emptyObj && !undefinedObj) {
    store.commit("CHANGE_HEADER", to.meta);
  } else {
    store.commit("CHANGE_HEADER", { showHeader: true, title: "" });
  }

  next();
});

export default RouterModel;
