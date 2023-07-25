<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramController extends Controller
{
    public function handle(Request $request, $chatId)
    {
        session_start();
        define('TOKEN', '6076207687:AAGizbRxmi7lMx7mDLl23azU7slAWQymdyk');

        $telegram = new Api(TOKEN);
        $update = $request->json()->all();

        if (isset($update['message'])) {
            $message = $update['message'];

            if (isset($message['text'])) {
                $text = $message['text'];
                $chatId = $message['chat']['id'];

                switch ($text) {
                    case '/start':
                        $this->handleStartCommand($telegram, $chatId, $message);
                        break;
                    case '/help':
                        $this->handleHelpCommand($telegram, $chatId);
                        break;
                    default:
                        $this->handleInput($telegram, $chatId, $text);
                        break;
                }
            }
        }

        return response('OK', 200);
    }

    private function handleStartCommand($telegram, $chatId, $message)
    {
        // Получаем текущее состояние пользователя из базы данных
        $user = User::where('telegram_id', $chatId)->first();

        if ($user) {
            if ($user->first_name === null) {
                // Запрашиваем имя пользователя
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Введите ваше имя:'
                ]);

                // Сохраняем состояние ожидания ввода имени
                $user->state = 'first_name';
                $user->save();
            } elseif ($user->last_name === null) {
                // Запрашиваем фамилию пользователя
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Введите вашу фамилию:'
                ]);

                // Сохраняем состояние ожидания ввода фамилии
                $user->state = 'last_name';
                $user->save();
            } else {
                // Пользователь уже зарегистрирован
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы уже зарегистрированы как : ' . $user->first_name . ' ' . $user->last_name,
                ]);
            }
        } else {
            // Создаем новую запись пользователя
            $newUser = new User();
            $newUser->telegram_id = $chatId;
            $newUser->username = $message['from']['username'];
            $newUser->state = 'first_name'; // Устанавливаем состояние ожидания ввода имени
            $newUser->save();

            // Запрашиваем имя пользователя
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Введите ваше имя:'
            ]);
        }
    }

    private function handleHelpCommand($telegram, $chatId)
    {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Доступные команды:'
                . PHP_EOL . '/start - Начать регистрацию'
                . PHP_EOL . '/help - Показать список команд'
        ]);
    }

    private function handleInput($telegram, $chatId, $text)
    {
        // Получаем текущее состояние пользователя из базы данных
        $user = User::where('telegram_id', $chatId)->first();

        if ($user) {
            if ($user->first_name === null) {
                // Сохраняем имя пользователя
                $user->first_name = $text;
                $user->save();

                // Запрашиваем фамилию пользователя
                $this->requestLastName($telegram, $chatId);
            } elseif ($user->last_name === null) {
                // Сохраняем фамилию пользователя
                $user->last_name = $text;
                $user->save();

                // Регистрация завершена
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Вы успешно зарегистрированы как :' . $user->first_name . ' ' . $user->last_name,
                ]);

                // Сбрасываем состояние пользователя
                $user->state = null;
                $user->save();
            }
        } else {
            // Пользователь не найден
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте снова.',
            ]);
        }
    }

    private function sendMessage($chatId, $text)
    {
        $telegram = new Api(TOKEN);

        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }


    private function requestLastName($telegram, $chatId)
    {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Введите вашу фамилию:'
        ]);
    }

}




