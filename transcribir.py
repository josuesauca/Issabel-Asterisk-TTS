from flask import Flask, request, jsonify
from vosk import Model, KaldiRecognizer
import wave
import json
import unicodedata

app = Flask(__name__)

# Cargar el modelo de Vosk
model = Model("vosk-model-small-es-0.42")

def clean_text(text):
    return ''.join(c for c in text if unicodedata.category(c)[0] != 'C')

"""
def transcribe_audio(audio_path):
    wf = wave.open(audio_path, "rb")
    rec = KaldiRecognizer(model, wf.getframerate())
    result_text = ""

    while True:
        data = wf.readframes(4000)
        if len(data) == 0:
            break
        if rec.AcceptWaveform(data):
            result = json.loads(rec.Result())
            result_text += result.get("text", "") + " "

    final_result = json.loads(rec.FinalResult())
    result_text += final_result.get("text", "")
    
    return result_text
    #return result_text.encode("utf-8", "ignore").decode("utf-8")
"""

def transcribe_audio(audio_path):
    wf = wave.open(audio_path, "rb")
    rec = KaldiRecognizer(model, wf.getframerate())
    result_text = ""

    while True:
        data = wf.readframes(4000)
        if len(data) == 0:
            break
        if rec.AcceptWaveform(data):
            result = json.loads(rec.Result())
            result_text += result.get("text", "") + " "

    final_result = json.loads(rec.FinalResult())
    result_text += final_result.get("text", "")

    # Limpiar caracteres problemáticos
    result_text = clean_text(result_text)
    
    return result_text.encode("utf-8", "ignore").decode("utf-8")


@app.route('/transcribe', methods=['POST'])
def transcribe():
    if 'audio' not in request.files:
        return jsonify({"error": "No audio file found"}), 400
    
    audio_file = request.files['audio']
    audio_path = f"./uploads/{audio_file.filename}"

    audio_file.save(audio_path)

    # Transcribir el audio
    transcribed_text = transcribe_audio(audio_path)

    # Imprimir para depuración
    print(f"Transcripción: {transcribed_text}")

    # Crear JSON con codificación segura
    response = {"transcription": transcribed_text}
    
    return json.dumps(response, ensure_ascii=False).encode("utf8"), 200, {'Content-Type': 'application/json; charset=utf-8'}


"""
@app.route('/transcribe', methods=['POST'])
def transcribe():
    if 'audio' not in request.files:
        return jsonify({"error": "No audio file found"}), 400
    
    audio_file = request.files['audio']
    #audio_path = f"./uploads/{audio_file.filename}"
    #audio_path = f"/var/lib/asterisk/agi-bin/{audio_file.filename}"
    audio_path = f"./uploads/{audio_file.filename}"

    audio_file.save(audio_path)

    # Transcribir el audio
    transcribed_text = transcribe_audio(audio_path)
    #print(" test ", transcribed_text)
    app.logger.info('Este es un mensaje informativo ', transcribed_text)
    
    # Borrar archivo después de procesarlo (opcional)
    # os.remove(audio_path)

    return jsonify({"transcription": transcribed_text})
"""

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=7880, debug=True)




"""
import speech_recognition as sr

r = sr.Recognizer()

with sr.AudioFile("prueba.wav") as source:
    r.adjust_for_ambient_noise(source, duration=0.5)  # Ajuste más rápido
    audio = r.record(source, duration=None)  # Puede ser más corto si el audio es largo

try:
    print("Espere un momento, el audio se está leyendo..")
    text = r.recognize_google(audio, language="es-ES", show_all=False)
    print(text.strip() + ".")  # Eliminar espacios extra y agregar punto
except sr.UnknownValueError:
    print("No se pudo entender el audio.")
except sr.RequestError:
    print("Error con el servicio de Google Speech-to-Text.")

"""


"""
from flask import Flask, request, jsonify
import speech_recognition as sr
import os

app = Flask(__name__)

@app.route('/transcribe', methods=['POST'])
def transcribe_audio():
    if 'audio' not in request.files:
        return jsonify({'error': 'No se envió un archivo de audio'}), 400

    audio_file = request.files['audio']
    file_path = "temp_audio.wav"
    audio_file.save(file_path)  # Guarda temporalmente el archivo

    recognizer = sr.Recognizer()

    try:
        with sr.AudioFile(file_path) as source:
            audio_data = recognizer.record(source)  # Captura el audio
            text = recognizer.recognize_google(audio_data, language='es-ES')  # Transcribe
        
        os.remove(file_path)  # Elimina el archivo temporal
        return jsonify({'transcription': text})

    except Exception as e:
        os.remove(file_path)  # Asegura que el archivo se elimine incluso si hay errores
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
"""
