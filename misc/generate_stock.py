from os import listdir, path
import cv2

sprite = "projects" 
# sprite = "action"
# sprite = "numero"


if sprite == "numero":
    mypath = "E:\WELCOME_TO\Welcome\CartesNumeros" #Had to rename the folder, accents in folder names seems to cause troubles.
    selected_files = [f for f in listdir(mypath) if f.endswith(".png")]
    # selected_files.append("CartesNumeros81.png") # Bug chelou qui m'oblige à rajouter un élément inutile à la fin (le dernier élément n'est pas utilisable :s)
    selected_files.sort(key=lambda x : int(x[12:-4])) 
    list_im = [path.join(mypath, f) for f in selected_files]
    print(list_im)
    output_path = "C:\\Users\Geoffrey\workspace\BGA\welcometo\img\construction_number_sprite.png"
    dim = (210, 280)

if sprite == "action":
    mypath = "E:\WELCOME_TO\Welcome\CartesActions"
    selected_files = [f for f in listdir(mypath) if f.endswith(".png")]
    # selected_files.append("CartesActions81.png") # Bug chelou qui m'oblige à rajouter un élément inutile à la fin (le dernier élément n'est pas utilisable :s)

    selected_files.sort(key=lambda x : int(x[12:-4])) 
    list_im = [path.join(mypath, f) for f in selected_files]
    output_path = "C:\\Users\Geoffrey\workspace\BGA\welcometo\img\construction_actions_sprite.png"
    dim = (210, 280)

if sprite == "projects":
    mypath_recto = "E:\WELCOME_TO\Welcome\CartesPlansRecto"
    selected_files_recto = [f for f in listdir(mypath_recto) if (f.endswith(".png") and not f.startswith("Carte_Solo"))] 
    # This sorting ensures a sort as expected per card number
    selected_files_recto.sort(key=lambda x : int(x[10:-4])) 
    list_im_recto = [path.join(mypath_recto, f) for f in selected_files_recto]
    # Same for verso cards
    mypath_verso = "E:\WELCOME_TO\Welcome\CartesPlansVerso"
    selected_files_verso = [f for f in listdir(mypath_verso) if f.endswith(".png")]
    # This sorting ensures a sort as expected per card number
    selected_files_verso.sort(key=lambda x : int(x[16:-4])) 
    list_im_verso = [path.join(mypath_verso, f) for f in selected_files_verso]

    output_path = "C:\\Users\Geoffrey\workspace\BGA\welcometo\img\plans_sprite.png"
    dim = (210, 280)

    list_im = list_im_recto + list_im_verso

dim = (105, 140)
imgs = [cv2.imread(i) for i in list_im]

def resize(img):
    # scale_percent = 25  # percent of original size
    # width = int(img.shape[1] * scale_percent / 100)
    # height = int(img.shape[0] * scale_percent / 100)
    # dim = (width, height)
    return cv2.resize(img, dim, interpolation=cv2.INTER_AREA)

im_v = cv2.hconcat(list(map(resize, imgs)))
# cv2.imwrite('img/cubirds_cards.png', im_v)
cv2.imwrite(output_path, im_v, [cv2.IMWRITE_PNG_COMPRESSION, 9])
