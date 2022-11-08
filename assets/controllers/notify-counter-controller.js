import {ApplicationController} from 'stimulus-use'
import router from "../utils/routing";
import {fetch, ok} from '../utils/http';


export default class extends ApplicationController {
    static targets = ['notifications', 'messages']
    static classes = ['hidden']

    connect() {
        super.connect();

        if (window.KBIN_LOGGED_IN) {
            this.updateCounter();
        }
    }

    async notification(event) {
        if (!this.hasNotificationsTarget) {
            return;
        }

        this.updateCounter();
    }

    async updateCounter() {
        let response = {
            count: 1
        };

        if (window.KBIN_LOGGED_IN) {
            response = {
                count: 0
            };

            // const url = router().generate('ajax_fetch_user_notifications_count', {username: window.KBIN_USERNAME});
            //
            // response = await fetch(url);
            //
            // response = await ok(response);
            // response = await response.json();
        }

        if (response.count > 0) {
            this.notificationsTarget.classList.remove('visually-hidden');

            let elem = this.notificationsTarget.getElementsByTagName('span')[0];
            elem.innerHTML = response.count;
        }
    }

    message(event) {
        if (!this.hasMessagesTarget) {
            return;
        }

        let elem = this.messagesTarget.getElementsByTagName('span')[0];
        elem.innerHTML = parseInt(elem.innerHTML) + 1;

        this.messagesTarget.classList.remove(this.hiddenClass);
    }
}
