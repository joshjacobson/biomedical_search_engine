
# coding: utf-8

# In[229]:

import igraph as ig
import json
import sys

with open('cui_network.json') as json_data: #reads in the data
    d = json.load(json_data)


# In[230]:

import plotly
import plotly.plotly as py
from plotly.graph_objs import *
py.sign_in('user', 'key')

# In[231]:

sys_equals = sys.argv #reads in the parameters from the console
selected_node = sys_equals[1] #the query node

# In[232]:

N = len(d['nodes'])
L = len(d['edges']) #L for links


# In[233]:

#This section relabels the nodes for memory optimization and identifies the query node

i=0
orig=[]
new=[]
node_colors=[]
font_sizes=[]

for node in range(N):
    orig.append(d['nodes'][node]['id'])
    new.append(i)
    
    if d['nodes'][node]['id'] == selected_node: #identifying query node to be highlighted
        coded_selected_node = i
        node_colors.append('red')
        font_sizes.append(16)
    else:
        node_colors.append('yellow')
        font_sizes.append(10)
    
    d['nodes'][node]['id'] = i
    i+=1


# In[234]:

# this section parses the edges
for edge in range(L):
    for j in range(N):
        if orig[j] == d['edges'][edge]['source']:
            d['edges'][edge]['source'] = new[j]
        if orig[j] == d['edges'][edge]['target']:
            d['edges'][edge]['target'] = new[j]


# In[235]:

#stores the edges as a paired list and passes them to igraph
Edges=[(int(d['edges'][k]['source']), int(d['edges'][k]['target'])) for k in range(L)]

G=ig.Graph(Edges, directed=True)


# In[236]:

#Parses the nodes and node parameters
labels2=[]
group=[]
definition=[]
ids=[]
abbvs=[]

for nodename in d['nodes']:
    try:
        labels2.append(nodename['Name'])
#        abbvs.append(nodename['Abbreviation'])
    except:
        labels2.append(nodename['Label']) #Label?

    try:
        definition.append(nodename['Definition'])
    except:
        definition.append('Definition')
       
    group.append(nodename['label'])
    ids.append(nodename['id'])


# In[237]:

#Specifies Kamada-Kawai 3d network layout
layt=G.layout('kk', dim=3)


# In[238]:

Xn=[layt[k][0] for k in range(N)]# x-coordinates of nodes
Yn=[layt[k][1] for k in range(N)]# y-coordinates
Zn=[layt[k][2] for k in range(N)]# z-coordinates
Xe=[]
Ye=[]
Ze=[]
for e in Edges:
    Xe+=[layt[e[0]][0],layt[e[1]][0], None]# x-coordinates of edge ends
    Ye+=[layt[e[0]][1],layt[e[1]][1], None]
    Ze+=[layt[e[0]][2],layt[e[1]][2], None]


# In[239]:

#Trace nodes, edges, plot in 3d, with proper formatting
trace1=Scatter3d(x=Xe,
               y=Ye,
               z=Ze,
               mode='lines',
               text=labels2,
               line=Line(color='rgb(125,125,125)', width=1),
               )
trace2=Scatter3d(x=Xn,
               y=Yn,
               z=Zn,
               mode='markers+text',
               name='UMLS',
               marker=Marker(symbol='dot',
                             size=6,
                             color=node_colors,
                             colorscale='Viridis',
                             line=Line(color='rgb(50,50,50)', width=0.5)
                             ),
               text=labels2,
               textfont={
                   "size": font_sizes}
               
               #hoverinfo=labels2,
               )


# In[240]:

#No axis formatting
axis=dict(showbackground=False,
          showline=False,
          zeroline=False,
          showgrid=False,
          showticklabels=False,
          title=''
          )


# In[241]:

layout = Layout(
         title="",
         width=1000,
         height=1000,
         showlegend=False,
         scene=Scene(
         xaxis=XAxis(axis),
         yaxis=YAxis(axis),
         zaxis=ZAxis(axis),
        ),
     margin=Margin(
        t=100
    ),
    hovermode='closest',
    annotations=Annotations([
           Annotation(
           showarrow=False,
            text="Data Source: UMLS",
            xref='paper',
            yref='paper',
            x=0,
            y=0.1,
            xanchor='left',
            yanchor='bottom',
            font=Font(
            size=14
            )
            )
        ]), hiddenlabels =labels2  )


# In[247]:

#Specify data and plot
data=Data([trace1, trace2])
fig=Figure(data=data, layout=layout)

url = py.plot(fig)#, filename='UMLS_Production')


# In[249]:

print(url+'.embed')


# In[ ]:



