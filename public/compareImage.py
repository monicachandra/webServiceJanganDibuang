import pickle
from keras.preprocessing import image

import numpy as np
import matplotlib.pyplot as plt
import os
import sys
        
features = pickle.load(open('fileFeatures.pkl', 'rb'))
images   = pickle.load(open('nameFeatures.pkl', 'rb'))

foto_path = sys.argv[1]
query_image_idx=-1;
for i, image_path in enumerate(images):
    if foto_path == image_path:
        query_image_idx=i;

img = image.load_img(images[query_image_idx])

oneDArray = []

for index,value in enumerate(features):
    oneDArray.append(np.ravel(features[index]))

from scipy.spatial import distance

def get_closest_images(query_image_idx):
    distances = [ distance.cosine(oneDArray[query_image_idx], feat) for feat in oneDArray ]
    idx_closest = []
    for index,value in enumerate(distances):
        if(value<=0.75):
            idx_closest.append(index)
    if(len(idx_closest)>1):
        return idx_closest[1:]
    else:
        return []

idx_closest = get_closest_images(query_image_idx)
variabelbaru2 ='';
for i in idx_closest:
    variabelbaru2 += images[i] + ","

variabelbaru2 = variabelbaru2[:-1]
print(variabelbaru2)