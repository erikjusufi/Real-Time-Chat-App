from functools import partial
from flask import Flask, request, jsonify
import time
app = Flask(__name__)
import websockets
import json
from multiprocessing import Process,Manager
from flask_cors import CORS


CORS(app)
# dictionary to store the last message from each user
import asyncio
connected_clients = dict()


@app.route('/polling/<username>', methods=['GET','POST'])
def handle_polling(username):
    if request.method == "GET":
        if username in last_messages.keys():
            msg = last_messages[username]
            del last_messages[username]
            return msg,200
        else:
            return "No messages",204

    elif request.method == "POST":
        data = request.get_json()
        last_messages[data["toUser"]] = data["message"]
        print(last_messages)
        return data["message"], 200


@app.route('/long-polling/<username>', methods=['GET', 'POST'])
def handle_long_polling(username):
    if request.method == "GET":
        while username not in last_messages.keys():
            time.sleep(0.5)
        msg = last_messages[username]
        del last_messages[username]
        return msg,200
    elif request.method == "POST":
        data = request.get_json()
        last_messages[data["toUser"]] = data["message"]
        return data["message"], 200
    print(last_messages)


async def chat_handler(websocket, path, last_messages):
    message = await websocket.recv()
    message = json.loads(message)
    connected_clients[message['username']] = websocket
    #check_process = Process(target=check_for_messages, args=(last_messages,websocket,))
    try:
        #check_process.start()
        while True:
            for client in connected_clients.keys():
                if connected_clients[client] == websocket:
                    if client in last_messages:
                        print(last_messages[client])
                        await asyncio.wait([connected_clients[client].send(json.dumps({"message":last_messages[client]}))])
                        del last_messages[client]
            task = asyncio.create_task(asyncio.wait_for(websocket.recv(),timeout=1.0))
            try:
                message = await asyncio.shield(task)
            except asyncio.CancelledError:
                continue
            except asyncio.TimeoutError:
                continue
            message = json.loads(message)
            last_messages[message["toUser"]] = message["message"]
            #await asyncio.wait([client.send(json.dumps(message)) for client in connected_clients.values()])
            if message["toUser"] in connected_clients:
                await asyncio.wait([connected_clients[message['toUser']].send(json.dumps(message))])
                del last_messages[message["toUser"]]
    except websockets.exceptions.ConnectionClosed:
        for id,socket in connected_clients.copy().items():
            if socket == websocket:
                del connected_clients[id]

async def f(last_messages):
    statefull_chat_handler = partial(chat_handler,last_messages=last_messages)
    async with websockets.serve(statefull_chat_handler, "localhost", 8765):
        await asyncio.Future()  # run forever
def start_process(last_messages):
    asyncio.run(f(last_messages))

'''async def check_for_messages(last_messages,websocket):
    websocket = websocket
    while True:
        time.sleep(0.5)
        for client in connected_clients.keys():
            if connected_clients[client] == websocket:
                if client in last_messages:
                    print(last_messages[client])
                    await asyncio.wait([connected_clients[client].send(json.dumps({"message":last_messages[client]}))])
                    del last_messages[client]
'''
if __name__ == '__main__':
    with Manager() as manager:
        last_messages = manager.dict()
        p = Process(target=start_process, args=(last_messages,))
        p.start()
        app.run(threaded=True, port=5000)

    
    