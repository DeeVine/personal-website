/**************************************************************************************************************************************************************************************
Darren Kwong
CIS 22A
6/20/14

This program asks the user to input a file name that they would like to read in. The numbers from the file are then input into an array and the user is given 6 options to choose from.
The user may choose from options 1-6 (calculate average, find highest, find lowest, find median, find mode, or exit program).
****************************************************************************************************************************************************************************************/

#include <iostream>
#include <fstream>
#include <string>
#include <stdlib.h>

using namespace std;

int getScores (int scores[], int max_scores);
double calcAverage (int scores[], int max_scores);
int findHighest (int scores[], int max_scores);
int findLowest (int scores[], int max_scores);
double getMedian (int scores[], int max_scores);
int getMode (int scores[], int max_scores);
void selectionSort(int scores[], int max_scores);

ifstream infile;
ofstream outfile;

const int max_scores = 40;
int scores[max_scores]; //array used to read in from file and hold scores
int selection;


int main()
{
    getScores(scores, max_scores); //read in scores to array

        do{
            cout << endl << "Please select an option between 1-6 below: " << endl << endl
            << "1: Calculate the average score\n"
            << "2: Find the highest score\n"
            << "3: Find the lowest score\n"
            << "4: Find the median score\n"
            << "5: Find the mode score\n"
            << "6: Exit program \n";
            cin >> selection;

            while(selection > 6 || selection < 1){ //input validation to ensure integer between 1-6 selected
                cout << endl<< "Sorry, you must input a choice between 1-6, please try again.\n";
                cin >> selection;

                while(!cin){ //input validation to ensure selection was an integer
                    cout << "That was not an integer! Please enter an integer between 1-6: ";
                    cin.clear();
                    cin.ignore();
                    cin >> selection;
                }
            }

            switch(selection){

                case 1:
                    cout << endl << "The average score = " << calcAverage(scores, max_scores) << endl;
                    break;
                case 2:
                    cout << endl << "The highest score = " << findHighest(scores, max_scores) << endl;
                    break;
                case 3:
                    cout << endl << "The lowest score = " << findLowest(scores, max_scores) << endl;
                    break;
                case 4:
                    cout << endl <<"The median score = " << getMedian(scores, max_scores) << endl;
                    break;
                case 5:
                    cout << endl << "The mode = " << getMode (scores, max_scores) << endl;
                    break;
                case 6:
                    cout << endl << "Program ended. Good bye!" << endl;
                    exit(0);
            }
        }while(selection !=6);

    return 0;
}

int getScores (int scores[], int max_scores){

    string fileName;

    cout << "Please input the file name you would like to read in: ";
    getline(cin, fileName);

    infile.open(fileName.c_str()); //open file to read in
        if (!infile){
            cout << endl << "File open failure, please make sure you entered the file name correctly with file extension such as .txt" << endl;
            exit (0);
            }

    double score;
    int counter = 0; //used to increment array position while reading in scores

    while(infile >> score && counter < max_scores){ //reads in scores until all scores are read into array
        scores[counter] = score;
        counter++;
    }
}


double calcAverage (int scores[], int max_scores){ //calculates average
    double total;

    for(int i = 0; i < max_scores ; i++ ){
        total += scores[i];
    }
    return total/max_scores;
}

int findHighest (int scores[], int max_scores){ //finds highest score
    int highest = 0;

    for(int i = 0; i < max_scores; i++){
        if(scores[i]>highest)
            highest=scores[i];
    }
    return highest;
}

int findLowest (int scores[], int max_scores){ //finds lowest score
    int lowest = 100;
        for(int i = 0; i < max_scores; i++){
        if(scores[i]<lowest)
            lowest=scores[i];
    }
    return lowest;
}

double getMedian (int scores[], int max_scores){ //finds median

            int startScan, minIndex, minValue;

            for(startScan  = 0; startScan < (max_scores-1); startScan++){
                minIndex = startScan;
                minValue = scores[startScan];
                for(int index = startScan + 1; index < max_scores; index++){
                    if(scores[index] < minValue){
                        minValue = scores[index];
                        minIndex = index;
                    }
                }
                scores[minIndex] = scores[startScan];
                scores[startScan] = minValue;
            }

    return double((scores[10])+scores[11])/2;
}

int getMode (int scores[], int max_scores){ //finds mode

    const int RANGE = 100; // scores range from 0 to 100
    int freq[RANGE] = {0}; // counter for each score
    int i;
    int highest = 0;
    int frequency = 0;

    for(i = 0; i < max_scores; i++){ //increments the respective position in the frequency array each time a score shows up in the scores array.
        freq[scores[i]]++;
    }

    for (i=0; i <RANGE; i++){
        if (freq[i] > frequency){
                frequency = freq[i];
                highest = i;
        }
    }
    return highest;
}
