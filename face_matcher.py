import sys
import json
import os
import glob
import numpy as np
from PIL import Image, ImageFile
import face_recognition

# Tell Pillow to be forgiving with slightly corrupted image files
ImageFile.LOAD_TRUNCATED_IMAGES = True

def force_safe_image(file_path, max_size=800):
    """
    Safely loads an image, resizes it for speed, and converts it to 8-bit RGB.
    """
    try:
        with Image.open(file_path) as img:
            from PIL import ImageOps
            # Handle EXIF orientation (prevents rotated faces)
            img = ImageOps.exif_transpose(img)
            
            # 🔥 SPEED BOOST: Resize large images before AI processing 🔥
            # thumbnail() keeps the aspect ratio but shrinks the image down.
            # 800px is perfectly clear for the AI but 10x faster to process than 4K.
            img.thumbnail((max_size, max_size))
            
            # Force conversion to standard 8-bit RGB
            rgb_img = img.convert('RGB')
            
            # Convert to numpy array
            img_array = np.array(rgb_img, dtype=np.uint8)
            
            # Guarantee C-contiguous memory layout
            return np.ascontiguousarray(img_array)
    except Exception:
        return None

def find_matching_faces(uploaded_image_path, album_folder):
    try:
        if not os.path.exists(uploaded_image_path):
            return json.dumps({"error": "Uploaded image not found"})
        
        if not os.path.exists(album_folder):
            return json.dumps({"error": "Album folder not found"})
        
        # Load uploaded image securely
        uploaded_img = force_safe_image(uploaded_image_path)
        if uploaded_img is None:
            return json.dumps({"error": "Could not read uploaded image."})
            
        # Extract the facial encodings
        uploaded_encodings = face_recognition.face_encodings(uploaded_img)
        
        if len(uploaded_encodings) == 0:
            return json.dumps({"error": "No face detected in uploaded image. Please use a clearer photo."})
        
        target_face_encoding = uploaded_encodings[0]
        matching_images = []
        
        # Get all image files in the album
        image_files = []
        for ext in ('*.jpg', '*.jpeg', '*.png', '*.JPG', '*.JPEG', '*.PNG'):
            image_files.extend(glob.glob(os.path.join(album_folder, ext)))
        
        if len(image_files) == 0:
            return json.dumps({"error": "No images found in album"})
        
        # Compare with each album image
        for image_path in image_files:
            try:
                # Load album image (will automatically resize for speed)
                album_img = force_safe_image(image_path)
                if album_img is None:
                    continue 
                
                # Extract faces
                album_encodings = face_recognition.face_encodings(album_img)
                
                if len(album_encodings) > 0:
                    # Compare faces
                    results = face_recognition.compare_faces(album_encodings, target_face_encoding, tolerance=0.55)
                    
                    if True in results:
                        matching_images.append(os.path.basename(image_path))
                        
            except Exception:
                continue
        
        matching_images = list(set(matching_images))
        
        if matching_images:
            return json.dumps({"matches": matching_images})
        else:
            return json.dumps({"error": "No match found"})
            
    except Exception as e:
        return json.dumps({"error": "Processing error: " + str(e)})

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Missing arguments"}))
        sys.exit(1)
    
    result = find_matching_faces(sys.argv[1], sys.argv[2])
    print(result)