import os
import sys
import keras
import tensorflow as tf
from keras.preprocessing import image
from keras.applications.imagenet_utils import preprocess_input
from keras.models import Model
import pickle

import numpy as np
import matplotlib.pyplot as plt

feat_extractor = tf.keras.applications.EfficientNetV2B0(weights='imagenet',include_top=False)
features = pickle.load(open('fileFeatures.pkl', 'rb'))
features = features.tolist()
images   = pickle.load(open('nameFeatures.pkl', 'rb'))

foto_path = sys.argv[1]

def load_image(path):
    img = image.load_img(path, target_size=(224,224,3))
    x = image.img_to_array(img)
    x = np.expand_dims(x, axis=0)
    x = preprocess_input(x)
    return img, x

img, x = load_image(foto_path);
feat = feat_extractor.predict(x)[0]
features.append(feat)

images.append(foto_path)

features = np.array(features)

file_features = open('fileFeatures.pkl', 'wb') 
pickle.dump(features, file_features)

file_name_features = open('nameFeatures.pkl', 'wb') 
pickle.dump(images, file_name_features)